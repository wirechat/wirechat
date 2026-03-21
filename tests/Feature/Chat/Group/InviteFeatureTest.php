<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Group\InviteLink;
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
        ->assertSee(__('wirechat::chat.group.invite_link.heading.label'));

    expect($conversation->group->inviteLinks()->count())->toBe(1);
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

it('can reset the active invite link', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(InviteLink::class, ['conversation' => $conversation])
        ->call('resetLink');

    $group = $conversation->group->fresh();

    expect($group->inviteLinks()->count())->toBe(2);
    expect($group->inviteLinks()->whereNotNull('revoked_at')->count())->toBe(1);
    expect($group->inviteLinks()->active()->count())->toBe(1);
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
    ]);

    Livewire::actingAs($owner)
        ->test(SendInviteLink::class, ['conversation' => $conversation, 'invite' => $invite])
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->call('save');

    $privateConversation = $owner->createConversationWith($receiver);

    expect($privateConversation->messages()->count())->toBe(1)
        ->and($privateConversation->messages()->latest('id')->first()->body)->toContain($invite->url(testPanelProvider()));
});

it('shows the invite preview page', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $receiver = User::factory()->create(['name' => 'Receiver']);

    $conversation = $owner->createGroup('Test Group', 'A great group');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
    ]);

    $this->actingAs($receiver)
        ->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertOk()
        ->assertSee('Test Group')
        ->assertSee(__('wirechat::chat.group.invite_link.page.actions.join_group.label'));
});

it('joins a group from an invite link when approval is not required', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
    ]);

    $this->actingAs($receiver)
        ->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('creates a join request when admin approval is required', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->admins_must_approve_new_members = true;
    $conversation->group->save();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
    ]);

    $this->actingAs($receiver)
        ->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertRedirect(testPanelProvider()->inviteRoute($invite->token));

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->hasPendingJoinRequest($receiver))->toBeTrue();
});

it('allows an exited participant to rejoin via an invite link', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $participant = $conversation->addParticipant($receiver);
    $participant->exitConversation();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
    ]);

    $this->actingAs($receiver)
        ->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $participant->refresh();

    expect($participant->exited_at)->toBeNull()
        ->and($receiver->belongsToConversation($conversation))->toBeTrue();
});
