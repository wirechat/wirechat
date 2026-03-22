<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Group\CreateInviteLink;
use Wirechat\Wirechat\Livewire\Chat\Group\InviteLink;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinFromInvite;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinRequests;
use Wirechat\Wirechat\Livewire\Chat\Group\Permissions;
use Wirechat\Wirechat\Livewire\Chat\Group\SendInviteLink;
use Wirechat\Wirechat\Models\Invite;
use Workbench\App\Models\User;

it('shows and updates the admin approval toggle in group permissions', function () {
    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createGroup('Test');
    $conversation->addParticipant($receiver);

    $request = Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation]);

    $request
        ->assertSee(__('wirechat::chat.group.permissions.actions.admin_approval.label'))
        ->assertSee(__('wirechat::chat.group.permissions.actions.admin_approval.helper_text'))
        ->assertPropertyWired('admins_must_approve_new_members');

    $request->set('admins_must_approve_new_members', true);

    expect($conversation->group->fresh()->admins_must_approve_new_members)->toBeTrue();
});

it('allows participants with add-members permission to access invite links', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->addParticipant($participant);
    $conversation->group->allow_members_to_add_others = true;
    $conversation->group->save();

    Livewire::actingAs($participant)
        ->test(InviteLink::class, ['conversation' => $conversation])
        ->assertStatus(200)
        ->assertSee('Invite Links');

    $invite = $conversation->group->inviteLinks()->first();

    expect($conversation->group->inviteLinks()->count())->toBe(1)
        ->and($invite?->is_primary)->toBeTrue();
});

it('forbids participants from accessing invite links when add-members permission is off', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->addParticipant($participant);
    $conversation->group->allow_members_to_add_others = false;
    $conversation->group->save();

    Livewire::actingAs($participant)
        ->test(InviteLink::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

it('can reset the active primary invite link', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(InviteLink::class, ['conversation' => $conversation])
        ->call('resetLink');

    $group = $conversation->group->fresh();
    $activeInvite = $group->inviteLinks()->active()->first();

    expect($group->inviteLinks()->count())->toBe(2)
        ->and($group->inviteLinks()->whereNotNull('revoked_at')->count())->toBe(1)
        ->and($group->inviteLinks()->active()->count())->toBe(1)
        ->and($activeInvite?->is_primary)->toBeTrue();
});

it('can create an additional invite link', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(InviteLink::class, ['conversation' => $conversation])
        ->assertStatus(200);

    Livewire::actingAs($owner)
        ->test(CreateInviteLink::class, ['conversation' => $conversation])
        ->set('name', 'Launch Team')
        ->set('expiryPreset', '1_day')
        ->set('usagePreset', '10')
        ->call('createLink')
        ->assertDispatched('refreshGroupInvites');

    $additionalInvite = $conversation->group->inviteLinks()->additional()->first();

    expect($conversation->group->inviteLinks()->count())->toBe(2)
        ->and($additionalInvite)->not->toBeNull()
        ->and($additionalInvite?->name)->toBe('Launch Team')
        ->and($additionalInvite?->limit)->toBe(10)
        ->and($additionalInvite?->expires_at)->not->toBeNull();
});

it('can send an invite link via chat', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $receiver = User::factory()->create(['name' => 'Receiver']);

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(SendInviteLink::class, ['conversation' => $conversation, 'invite' => $invite])
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->call('save');

    $privateConversation = $owner->createConversationWith($receiver);

    expect($privateConversation->messages()->count())->toBe(1)
        ->and($privateConversation->messages()->latest('id')->first()->body)->toContain($invite->url(testPanelProvider()));
});

it('shows the invite preview page to guests', function () {
    $owner = User::factory()->create(['name' => 'Owner']);

    $conversation = $owner->createGroup('Test Group', 'A great group');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertOk()
        ->assertSee('Test Group')
        ->assertSee('Join Group');
});

it('stages the invite token in session and redirects to chats index', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertRedirect(testPanelProvider()->chatsRoute())
        ->assertSessionHas('wirechat_pending_invite_token', $invite->token);
});

it('joins a public group from the in-app invite modal', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(JoinFromInvite::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('creates a join request from the in-app invite modal when approval is required', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(JoinFromInvite::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertNoRedirect();

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->hasPendingJoinRequest($receiver))->toBeTrue()
        ->and($invite->usages)->toBe(0);
});

it('allows an exited participant to rejoin via the in-app invite modal', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    $participant = $conversation->addParticipant($receiver);
    $participant->exitConversation();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(JoinFromInvite::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $participant->refresh();
    $invite->refresh();

    expect($participant->exited_at)->toBeNull()
        ->and($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('allows admins to approve join requests from the drawer', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $conversation->group->requestToJoin($receiver, $invite);
    $request = $conversation->group->pendingJoinRequests()->whereRequester($receiver)->firstOrFail();

    Livewire::actingAs($owner)
        ->test(JoinRequests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('approve', $request->id);

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($request->fresh()->status)->toBe(JoinRequestStatus::ACCEPTED)
        ->and($request->fresh()->reviewed_at)->not->toBeNull()
        ->and($invite->usages)->toBe(1);
});

it('allows admins to dismiss join requests from the drawer', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $conversation->group->requestToJoin($receiver, $invite);
    $request = $conversation->group->pendingJoinRequests()->whereRequester($receiver)->firstOrFail();

    Livewire::actingAs($owner)
        ->test(JoinRequests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('dismiss', $request->id);

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($request->fresh()->status)->toBe(JoinRequestStatus::DISMISSED)
        ->and($request->fresh()->reviewed_at)->not->toBeNull()
        ->and($invite->usages)->toBe(0);
});

it('shows the join request banner only to group admins', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $requester = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->addParticipant($member);
    $conversation->group->requestToJoin($requester);

    Livewire::actingAs($owner)
        ->test(Chat::class, ['conversation' => $conversation])
        ->assertSee('Join Request');

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $conversation])
        ->assertDontSee('Join Request');
});
