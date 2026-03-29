<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Group\Info as GroupInfo;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinFromInvite;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinRequests;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Create;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Links;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Send;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Show;
use Wirechat\Wirechat\Livewire\Chat\Group\Permissions;
use Wirechat\Wirechat\Models\Invite;
use Workbench\App\Models\User;

beforeEach(function () {
    testPanelProvider()->groupInvitations(true);
});

it('shows and updates the admin approval toggle in group permissions', function () {
    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createGroup('Test');
    $conversation->addParticipant($receiver);

    $request = Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

    $request
        ->assertSee(__('wirechat::chat.group.permissions.actions.admin_approval.label'))
        ->assertSee(__('wirechat::chat.group.permissions.actions.admin_approval.helper_text'))
        ->assertPropertyWired('admins_must_approve_new_members');

    $request->set('admins_must_approve_new_members', true);

    expect($conversation->group->fresh()->admins_must_approve_new_members)->toBeTrue();
});

it('updates the invite link access label when admin approval changes', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $component = Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.invite_link.labels.group_access_open'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.group_access_requires_approval'));

    $conversation->group->setAttribute('admins_must_approve_new_members', true)->save();

    $component
        ->call('$refresh')
        ->assertSee(__('wirechat::chat.group.invite_link.labels.group_access_requires_approval'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.group_access_open'));

    $conversation->group->setAttribute('admins_must_approve_new_members', false)->save();

    $component
        ->call('$refresh')
        ->assertSee(__('wirechat::chat.group.invite_link.labels.group_access_open'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.group_access_requires_approval'));
});

it('allows admins to access invite links', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $participant = $conversation->addParticipant($admin);
    $participant->role = ParticipantRole::ADMIN;
    $participant->save();

    Livewire::actingAs($admin)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertSee(__('wirechat::chat.group.invite_link.heading.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.labels.primary_link'))
        ->assertSee(__('wirechat::chat.group.invite_link.labels.additional_links'));

    $invite = $conversation->group->inviteLinks()->first();

    expect($conversation->group->inviteLinks()->count())->toBe(1)
        ->and($invite?->is_primary)->toBeTrue();
});

it('forbids non-admin participants from accessing invite link management even when they can add members', function () {
    $owner = User::factory()->create();
    $participantUser = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $participant = $conversation->addParticipant($participantUser);
    $participant->role = ParticipantRole::PARTICIPANT;
    $participant->save();
    $conversation->group->allow_members_to_add_others = true;
    $conversation->group->save();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($participantUser)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);

    Livewire::actingAs($participantUser)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);

    Livewire::actingAs($participantUser)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);

    Livewire::actingAs($participantUser)
        ->test(JoinRequests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);
});

it('can reset the active primary invite link', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
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
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200);

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
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

it('renders translated content in the create invite link modal', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.invite_link.create.heading.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.inputs.name.placeholder'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.inputs.name.helper_text'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.sections.expiry.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.sections.usage.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.options.expiry.1_hour'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.options.expiry.1_day'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.options.expiry.1_week'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.options.expiry.never'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.options.usage.unlimited'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.labels.approval_notice'))
        ->assertSee(__('wirechat::chat.group.invite_link.create.actions.create.label'));
});

it('renders translated content in the invite link details modal', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $conversation = $owner->createGroup('Test');

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => false,
        'usages' => 3,
    ]);

    Livewire::actingAs($owner)
        ->test(Show::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.invite_link.show.heading.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.link'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.created_by'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.uses'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.limit'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.unlimited'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.expires'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.labels.never'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.actions.copy_link.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.actions.share_link.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.show.actions.revoke.label'));
});

it('marks the selected usage preset as active in the create invite link modal', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->set('usagePreset', '10')
        ->assertSeeHtml('font-medium text-[var(--wc-brand-primary)]')
        ->assertSee('10');
});

it('updates the usage slider position when the selected usage preset changes', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->set('usagePreset', '10')
        ->assertSeeHtml('wire:key="usage-slider-10"')
        ->assertSeeHtml('value="1"')
        ->set('usagePreset', '100')
        ->assertSeeHtml('wire:key="usage-slider-100"')
        ->assertSeeHtml('value="3"');
});

it('renders clickable expiry and usage slider labels in the create invite link modal', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSeeHtml('wire:click="$set(\'expiryPreset\', \'1_day\')"')
        ->assertSeeHtml('wire:click="$set(\'expiryPreset\', \'never\')"')
        ->assertSeeHtml('wire:click="$set(\'usagePreset\', \'10\')"')
        ->assertSeeHtml('wire:click="$set(\'usagePreset\', \'unlimited\')"');
});

it('validates the create invite link name length', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->set('name', str_repeat('a', 121))
        ->call('createLink')
        ->assertHasErrors(['name' => 'max']);
});

it('validates the create invite link expiry preset', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->set('expiryPreset', '2_weeks')
        ->call('createLink')
        ->assertHasErrors(['expiryPreset' => 'in']);
});

it('validates the create invite link usage preset', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    Livewire::actingAs($owner)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->set('usagePreset', '999')
        ->call('createLink')
        ->assertHasErrors(['usagePreset' => 'in']);
});

it('loads additional invite links incrementally from the dedicated list component', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    foreach (range(1, 12) as $number) {
        $conversation->group->inviteLinks()->create([
            'panel_id' => testPanelProvider()->getId(),
            'created_by_id' => $owner->getKey(),
            'created_by_type' => $owner->getMorphClass(),
            'token' => Invite::generateToken(),
            'name' => sprintf('Campaign %02d', $number),
            'is_primary' => false,
        ]);
    }

    Livewire::actingAs($owner)
        ->test(\Wirechat\Wirechat\Livewire\Chat\Group\Links\List::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.invite_link.labels.additional_links'))
        ->assertSee(__('wirechat::chat.group.invite_link.actions.load_more.label'))
        ->assertSee('Campaign 12')
        ->assertSee('Campaign 10')
        ->assertDontSee('Campaign 09')
        ->assertDontSee('Campaign 01')
        ->call('loadMore')
        ->assertSee('Campaign 09')
        ->assertSee('Campaign 01');
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
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->call('save');

    $privateConversation = $owner->createConversationWith($receiver);

    expect($privateConversation->messages()->count())->toBe(1)
        ->and($privateConversation->messages()->latest('id')->first()->body)->toContain($invite->url(testPanelProvider()));
});

it('hides exited and removed past members from send invite search results', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $exitedUser = User::factory()->create(['name' => 'Invite Candidate Left']);
    $removedUser = User::factory()->create(['name' => 'Invite Candidate Removed']);
    $availableUser = User::factory()->create(['name' => 'Invite Candidate Ready']);

    $conversation = $owner->createGroup('Test');
    $conversation->addParticipant($exitedUser)->exitConversation();
    $conversation->addParticipant($removedUser)->removeByAdmin($owner);

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->set('search', 'Invite Candidate')
        ->assertSee($availableUser->wirechat_name)
        ->assertDontSee($exitedUser->wirechat_name)
        ->assertDontSee($removedUser->wirechat_name);
});

it('rejects direct send invite selection for an exited past member', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $exitedUser = User::factory()->create(['name' => 'Exited User']);

    $conversation = $owner->createGroup('Test');
    $conversation->addParticipant($exitedUser)->exitConversation();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->call('toggleMember', $exitedUser->getKey(), $exitedUser->getMorphClass())
        ->assertStatus(403);
});

it('uses the panel user search callback in the send invite modal', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $emailMatchedUser = User::factory()->create([
        'name' => 'Email Result',
        'email' => 'custom-callback@example.com',
    ]);
    $nameMatchedUser = User::factory()->create([
        'name' => 'custom-callback',
        'email' => 'name-only@example.com',
    ]);

    testPanelProvider()->searchUsersUsing(function ($needle) {
        return User::query()
            ->where('email', 'like', "%{$needle}%")
            ->get();
    });

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->set('search', 'custom-callback')
        ->assertSee($emailMatchedUser->wirechat_name)
        ->assertDontSee($nameMatchedUser->wirechat_name);
});

it('shows invite management actions only to admins in group info', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $participantUser = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->allow_members_to_add_others = true;
    $conversation->group->save();

    $adminParticipant = $conversation->addParticipant($admin);
    $adminParticipant->role = ParticipantRole::ADMIN;
    $adminParticipant->save();

    $participant = $conversation->addParticipant($participantUser);
    $participant->role = ParticipantRole::PARTICIPANT;
    $participant->save();

    Livewire::actingAs($admin)
        ->test(GroupInfo::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.info.actions.add_members.label'))
        ->assertSee(__('wirechat::chat.group.info.actions.invite_via_link.label'))
        ->assertSee(__('wirechat::chat.group.invite_link.labels.join_requests'));

    Livewire::actingAs($participantUser)
        ->test(GroupInfo::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.info.actions.add_members.label'))
        ->assertDontSee(__('wirechat::chat.group.info.actions.invite_via_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.join_requests'));
});

it('shows group access editing only to owners inside invite links', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $participant = $conversation->addParticipant($admin);
    $participant->role = ParticipantRole::ADMIN;
    $participant->save();

    Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee('wirechat.chat.group.permissions');

    Livewire::actingAs($admin)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee('wirechat.chat.group.permissions')
        ->assertSee(__('wirechat::chat.group.invite_link.actions.create_new_link.label'));
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
        ->assertSee(__('wirechat::chat.group.invite_link.page.labels.invite_title'))
        ->assertSee(__('wirechat::chat.group.invite_link.page.messages.invited_to_join_at', ['app' => config('app.name')]))
        ->assertSee(__('wirechat::chat.group.invite_link.page.actions.continue.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.page.actions.join_group.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.page.actions.cancel.label'))
        ->assertSee('method="POST"', escape: false)
        ->assertSee('name="_token"', escape: false)
        ->assertSee(testPanelProvider()->inviteJoinRoute($invite->token), escape: false);
});

it('renders invite preview page using translations', function () {
    app()->setLocale('tr');

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
        ->assertSee(__('wirechat::chat.group.invite_link.page.labels.invite_title'))
        ->assertSee(__('wirechat::chat.group.invite_link.page.messages.invited_to_join_at', ['app' => config('app.name')]))
        ->assertSee(__('wirechat::chat.group.invite_link.page.actions.continue.label'));
});

it('renders join from invite modal using translations', function () {
    app()->setLocale('tr');

    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test Group', 'Harika bir grup');
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
        ->assertSee(__('wirechat::chat.group.join_from_invite.heading.label'))
        ->assertSee(__('wirechat::chat.group.join_from_invite.labels.members_count', ['count' => $conversation->participants_count]))
        ->assertSee(__('wirechat::chat.group.join_from_invite.labels.open_access'))
        ->assertSee(__('wirechat::chat.group.join_from_invite.actions.cancel.label'))
        ->assertSee(__('wirechat::chat.group.join_from_invite.actions.join_group.label'));
});

it('shows an overflow badge when the invite modal has more than six members to preview', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test Group');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    User::factory()->count(6)->create()->each(fn (User $user) => $conversation->addParticipant($user));

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(JoinFromInvite::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->assertSee('+1')
        ->assertSee(trans_choice('wirechat::chat.group.join_from_invite.labels.members_count', 7, ['count' => 7]));
});

it('rejects tampered invite tokens on the join endpoint', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $tamperedToken = Invite::generateToken();

    expect($tamperedToken)->not->toBe($invite->token);

    $this->post(testPanelProvider()->inviteJoinRoute($tamperedToken))
        ->assertNotFound()
        ->assertSessionMissing('wirechat_pending_invite_token');
});

it('rejects malformed invite tokens before hitting the controller', function () {
    $this->get(testPanelProvider()->inviteRoute('bad-token!!'))
        ->assertNotFound();

    $this->post(testPanelProvider()->inviteJoinRoute('bad-token!!'))
        ->assertNotFound()
        ->assertSessionMissing('wirechat_pending_invite_token');
});

it('returns gone for revoked invite links', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $invite->revoke();

    $this->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertStatus(410);

    $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertStatus(410)
        ->assertSessionMissing('wirechat_pending_invite_token');
});

it('rate limits repeated invite join attempts', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    foreach (range(1, 10) as $attempt) {
        $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
            ->assertRedirect(testPanelProvider()->chatsRoute());
    }

    $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertStatus(429);
});

it('hides and blocks group invitations when the panel disables them', function () {
    testPanelProvider()->groupInvitations(false);

    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertNotFound();

    Livewire::actingAs($owner)
        ->test(GroupInfo::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee(__('wirechat::chat.group.info.actions.invite_via_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.join_requests'));

    Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(404);
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

it('allows an admin-removed participant to rejoin via the in-app invite modal', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    $participant = $conversation->addParticipant($receiver);
    $participant->removeByAdmin($owner);

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

    expect($participant->isRemovedByAdmin())->toBeFalse()
        ->and($participant->isBlockedByAdmin())->toBeFalse()
        ->and($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('keeps blocked members from rejoining by invite until the block is lifted', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    $participant = $conversation->addParticipant($receiver);
    $participant->blockByAdmin($owner);

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

    $participant->refresh();
    $invite->refresh();

    expect($participant->isBlockedByAdmin())->toBeTrue()
        ->and($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($invite->usages)->toBe(0);

    $participant->liftBlockByAdmin();
    $participant->refresh();

    Livewire::actingAs($receiver)
        ->test(JoinFromInvite::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $participant->refresh();
    $invite->refresh();

    expect($participant->isBlockedByAdmin())->toBeFalse()
        ->and($participant->isRemovedByAdmin())->toBeFalse()
        ->and($participant->exited_at)->toBeNull()
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
        ->test(Chat::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee('Join Request');

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee('Join Request');
});
