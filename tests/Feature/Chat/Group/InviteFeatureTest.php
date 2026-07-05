<?php

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Wirechat\Wirechat\Enums\GroupType;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Group\Info as GroupInfo;
use Wirechat\Wirechat\Livewire\Chat\Group\Join\Lobby;
use Wirechat\Wirechat\Livewire\Chat\Group\Join\Requests;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Create;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Links;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\ListLinks;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Send;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Show;
use Wirechat\Wirechat\Livewire\Chat\Group\Permissions;
use Wirechat\Wirechat\Models\Invite;
use Workbench\App\Models\User;

beforeEach(function () {
    testPanelProvider()->registerRoutes(true);
    testPanelProvider()->groupInvitations(true);
    testPanelProvider()->inviteJoinRedirect(null);
    testPanelProvider()->mountUrl(null);
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

it('hides public invite url controls when panel routes are disabled', function () {
    testPanelProvider()->registerRoutes(false);

    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $linksComponent = Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.primary_link'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.additional_links'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.actions.copy_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.actions.send_via_chat.label'));

    $invite = $conversation->group->inviteLinks()->firstOrFail();

    $linksComponent->assertDontSee($invite->url(testPanelProvider()));

    Livewire::actingAs($owner)
        ->test(Show::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertDontSee(__('wirechat::chat.group.invite_link.show.actions.copy_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.show.actions.share_link.label'))
        ->assertDontSee($invite->url(testPanelProvider()));
});

it('keeps public invite url controls when chat routes are disabled and a mount url is configured', function () {
    testPanelProvider()
        ->registerRoutes(false)
        ->mountUrl('/app');

    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $linksComponent = Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertSee(__('wirechat::chat.group.invite_link.labels.primary_link'))
        ->assertSee(__('wirechat::chat.group.invite_link.actions.copy_link.label'));

    $invite = $conversation->group->inviteLinks()->firstOrFail();

    $linksComponent->assertSee($invite->url(testPanelProvider()));

    Livewire::actingAs($owner)
        ->test(Show::class, ['conversation' => $conversation, 'invite' => $invite, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertSee(__('wirechat::chat.group.invite_link.show.actions.copy_link.label'))
        ->assertSee($invite->url(testPanelProvider()));
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
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);
});

it('allows members with invite link permission to use only the primary invite link', function () {
    $owner = User::factory()->create();
    $participantUser = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $participant = $conversation->addParticipant($participantUser);
    $participant->role = ParticipantRole::PARTICIPANT;
    $participant->save();

    $conversation->group->allow_members_to_invite_others_via_link = true;
    $conversation->group->save();

    Livewire::actingAs($participantUser)
        ->test(GroupInfo::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.info.actions.invite_via_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.join_requests'));

    Livewire::actingAs($participantUser)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200)
        ->assertSee(__('wirechat::chat.group.invite_link.labels.primary_link'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.additional_links'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.actions.create_new_link.label'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.actions.reset_link.label'));

    $primaryInvite = $conversation->group->inviteLinks()->primary()->first();

    expect($primaryInvite)->not->toBeNull()
        ->and($primaryInvite?->is_primary)->toBeTrue();

    Livewire::actingAs($participantUser)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $primaryInvite, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200);

    Livewire::actingAs($participantUser)
        ->test(Create::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);

    Livewire::actingAs($participantUser)
        ->test(Show::class, ['conversation' => $conversation, 'invite' => $primaryInvite, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);

    $additionalInvite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => false,
    ]);

    Livewire::actingAs($participantUser)
        ->test(Send::class, ['conversation' => $conversation, 'invite' => $additionalInvite, 'panel' => testPanelProvider()->getId()])
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
        ->assertSeeHtml('copyWithSelection')
        ->assertSeeHtml('window.navigator.clipboard.writeText(value)')
        ->assertSeeHtml('copyWithClipboard().then((copied) => {')
        ->assertSeeHtml('if (copied || copyWithSelection())')
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
        ->test(ListLinks::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
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
        ->set('search', 'Receiver')
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->call('save')
        ->assertNotDispatched('refresh')
        ->assertDispatched('refresh-chats');

    $privateConversation = $owner->createConversationWith($receiver);

    expect($privateConversation->messages()->count())->toBe(1)
        ->and($privateConversation->messages()->latest('id')->first()->body)->toContain($invite->url(testPanelProvider()));
});

it('removes selected invite recipients after the search query changes', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $receiver = User::factory()->create(['name' => 'Receiver One']);
    User::factory()->create(['name' => 'Receiver Two']);

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
        ->set('search', 'Receiver One')
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->assertSee('Receiver One')
        ->set('search', 'Receiver Two')
        ->call('toggleMember', $receiver->getKey(), $receiver->getMorphClass())
        ->assertSet('selectedMembers', collect())
        ->assertDontSee('Receiver One');
});

it('ignores tampered send invite selections outside the current panel search results', function () {
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
        ->set('search', 'Receiver')
        ->call('toggleMember', $receiver->getKey(), Invite::class)
        ->assertSet('selectedMembers', collect());
});

it('rejects non-group inviteables on the join endpoint', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();
    $conversation = $owner->createConversationWith($receiver);

    $invite = Invite::query()->create([
        'panel_id' => testPanelProvider()->getId(),
        'inviteable_id' => $conversation->getKey(),
        'inviteable_type' => $conversation->getMorphClass(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertNotFound()
        ->assertSessionMissing('wirechat_pending_invite_token');
});

it('prevents sending a group invite link via chat to banned past members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $bannedUser = User::factory()->create();

    $groupConversation = $owner->createGroup('Core Team');
    $groupConversation->addParticipant($member)->update(['role' => ParticipantRole::ADMIN]);

    $participant = $groupConversation->addParticipant($bannedUser);
    $participant->banByAdmin($owner);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Send::class, [
            'conversation' => $groupConversation,
            'invite' => $invite,
            'panel' => testPanelProvider()->getId(),
        ])
        ->set('search', $bannedUser->name)
        ->call('toggleMember', $bannedUser->id, $bannedUser->getMorphClass())
        ->assertStatus(403);
});

it('hides exited and removed past members from send invite search results', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $exitedUser = User::factory()->create(['name' => 'Invite Candidate Left']);
    $removedUser = User::factory()->create(['name' => 'Invite Candidate Removed']);
    $availableUser = User::factory()->create([
        'name' => 'Invite Candidate Ready',
        'email' => 'invite.candidate@example.test',
    ]);

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
        ->assertSee($availableUser->wirechat_subtitle)
        ->assertDontSee($exitedUser->wirechat_name)
        ->assertDontSee($removedUser->wirechat_name);
});

it('shows requester subtitles in the join requests drawer', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create([
        'name' => 'Subtitle Requester',
        'email' => 'subtitle.requester@example.test',
    ]);

    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $conversation->group->requestToJoin($receiver, $invite);

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee($receiver->wirechat_name)
        ->assertSee($receiver->wirechat_subtitle);
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
        ->set('search', $exitedUser->name)
        ->call('toggleMember', $exitedUser->getKey(), $exitedUser->getMorphClass())
        ->assertStatus(403);
});

it('uses the panel user search callback in the send invite modal', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $emailMatchedUser = User::factory()->create([
        'name' => 'Email Result',
        'email' => 'callback-match@example.com',
    ]);
    $nameMatchedUser = User::factory()->create([
        'name' => 'Callback Match',
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
        ->set('search', 'callback-match')
        ->assertSee($emailMatchedUser->wirechat_name)
        ->assertDontSee($nameMatchedUser->wirechat_name);
});

it('shows invite management actions only to admins in group info', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $participantUser = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->allow_members_to_add_others = true;
    $conversation->group->admins_must_approve_new_members = true;
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
        ->assertSeeHtml('wirechat.chat.group.links.show')
        ->assertSeeHtml('wirechat.chat.group.links.send')
        ->assertSeeHtml('copyWithSelection')
        ->assertSeeHtml('window.navigator.clipboard.writeText(value)')
        ->assertSeeHtml("document.execCommand('copy')")
        ->assertSeeHtml('openChatDrawer')
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

it('redirects existing members from the invite preview to the group chat', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $member = User::factory()->create();

    $conversation = $owner->createGroup('Test Group');
    $conversation->addParticipant($member);

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->actingAs($member)
        ->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));
});

it('handleOpenChat redirects existing members to the chat in non-widget mode', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host'); // unrelated chat we render Chat in
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $groupConversation->addParticipant($member);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertRedirect(testPanelProvider()->chatRoute($groupConversation->id));
});

it('handleOpenChat opens existing member invites internally when panel routes are disabled', function () {
    testPanelProvider()->registerRoutes(false);

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $groupConversation->addParticipant($member);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt('http://localhost:8001/test/invites/'.$invite->token))
        ->assertNoRedirect()
        ->assertDispatched('open-chat', conversation: $groupConversation->id);
});

it('handleOpenChat resolves invites through the configured invite model', function () {
    $customInvite = new class extends Invite
    {
        public static bool $queried = false;

        public function newQuery()
        {
            self::$queried = true;

            return parent::newQuery();
        }
    };
    $customInviteClass = get_class($customInvite);

    config(['wirechat.models.invite' => $customInviteClass]);
    Wirechat::resetTableNameCache('invite');

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $groupConversation->addParticipant($member);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => 'CustomInviteToken01',
        'is_primary' => true,
    ]);

    $customInviteClass::$queried = false;

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertRedirect(testPanelProvider()->chatRoute($groupConversation->id));

    expect($customInviteClass::$queried)->toBeTrue();
});

it('handleOpenChat dispatches open-chat in widget mode for existing members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $groupConversation->addParticipant($member);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($member)
        ->test(Chat::class, [
            'conversation' => $hostConversation->id,
            'panel' => testPanelProvider()->getId(),
            'widget' => true,
        ])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertNoRedirect()
        ->assertDispatched('open-chat');
});

it('handleOpenChat opens the lobby modal for non-members in non-widget mode', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($outsider);

    $groupConversation = $owner->createGroup('Target');

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $component = Livewire::actingAs($outsider)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertNoRedirect();

    $jsEffects = data_get($component->effects, 'xjs', []);
    $payload = collect($jsEffects)->map(fn ($entry) => is_array($entry) ? ($entry['expression'] ?? '') : $entry)->implode("\n");

    expect($payload)->toContain("Livewire.dispatch('openWirechatModal'")
        ->and($payload)->toContain('wirechat.chat.group.join.lobby')
        ->and($payload)->toContain($invite->token);

    // No mutation has happened — the lobby will own the join confirmation.
    expect($outsider->belongsToConversation($groupConversation))->toBeFalse()
        ->and($invite->fresh()->usages)->toBe(0);
});

it('handleOpenChat opens the lobby modal for non-members in widget mode', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($outsider);

    $groupConversation = $owner->createGroup('Target');

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $component = Livewire::actingAs($outsider)
        ->test(Chat::class, [
            'conversation' => $hostConversation->id,
            'panel' => testPanelProvider()->getId(),
            'widget' => true,
        ])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertNoRedirect();

    $jsEffects = data_get($component->effects, 'xjs', []);
    $payload = collect($jsEffects)->map(fn ($entry) => is_array($entry) ? ($entry['expression'] ?? '') : $entry)->implode("\n");

    expect($payload)->toContain("Livewire.dispatch('openWirechatModal'")
        ->and($payload)->toContain('"widget":true')
        ->and($payload)->toContain($invite->token);

    expect($outsider->belongsToConversation($groupConversation))->toBeFalse();
});

it('handleOpenChat accepts the full invite URL and not just the token', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $groupConversation->addParticipant($member);

    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->url(testPanelProvider())))
        ->assertRedirect(testPanelProvider()->chatRoute($groupConversation->id));
});

it('handleOpenChat returns 404 for unrelated URLs', function () {
    $owner = User::factory()->create();
    $auth = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($auth);

    Livewire::actingAs($auth)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt('https://example.com/not-an-invite'))
        ->assertStatus(404);
});

it('handleOpenChat returns 404 for tampered (non-encrypted) input', function () {
    $owner = User::factory()->create();
    $auth = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($auth);

    $groupConversation = $owner->createGroup('Target');
    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    // Sending the raw (un-encrypted) token must not bypass the encryption gate.
    Livewire::actingAs($auth)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', $invite->token)
        ->assertStatus(404);
});

it('handleOpenChat returns 404 when the panel has group invitations disabled', function () {
    testPanelProvider()->groupInvitations(false);

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertStatus(404);
});

it('handleOpenChat returns 404 for short tokens that fall outside the route regex', function () {
    $owner = User::factory()->create();
    $auth = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($auth);

    Livewire::actingAs($auth)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt('abc'))
        ->assertStatus(404);
});

it('handleOpenChat throttles excessive calls', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    RateLimiter::clear('wirechat-open-chat:'.$member->getKey());

    $groupConversation = $owner->createGroup('Target');
    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    RateLimiter::increment('wirechat-open-chat:'.$member->getKey(), 60, 60);

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertStatus(429);
});

it('handleOpenChat returns 410 for revoked invite tokens', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $hostConversation = $owner->createGroup('Host');
    $hostConversation->addParticipant($member);

    $groupConversation = $owner->createGroup('Target');
    $invite = $groupConversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);
    $invite->revoke();

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $hostConversation->id, 'panel' => testPanelProvider()->getId()])
        ->call('handleOpenChat', encrypt($invite->token))
        ->assertStatus(410);
});

it('still shows the invite preview to authenticated non-members', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $outsider = User::factory()->create();

    $conversation = $owner->createGroup('Test Group');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->actingAs($outsider)
        ->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertOk()
        ->assertSee(__('wirechat::chat.group.invite_link.page.actions.continue.label'));
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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.join.lobby.heading.label'))
        ->assertSee(__('wirechat::chat.group.join.lobby.labels.members_count', ['count' => $conversation->participants_count]))
        ->assertSee(__('wirechat::chat.group.join.lobby.labels.open_access'))
        ->assertSee(__('wirechat::chat.group.join.lobby.actions.cancel.label'))
        ->assertSee(__('wirechat::chat.group.join.lobby.actions.join_group.label'))
        ->assertSeeHtml('autofocus tabindex="-1" class="text-lg font-semibold focus:outline-hidden"');
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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->assertSee('+1')
        ->assertSee(trans_choice('wirechat::chat.group.join.lobby.labels.members_count', 7, ['count' => 7]));
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
        ->assertSessionHas('wirechat_pending_invite_token', $invite->token)
        ->assertSessionHas('wirechat_pending_invite_panel', testPanelProvider()->getId());
});

it('stages the invite token in session and redirects to the mount url when chat routes are disabled', function () {
    testPanelProvider()
        ->registerRoutes(false)
        ->mountUrl('/app');

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
        ->assertRedirect('/app')
        ->assertSessionHas('wirechat_pending_invite_token', $invite->token)
        ->assertSessionHas('wirechat_pending_invite_panel', testPanelProvider()->getId());
});

it('redirects invite joins to the panel inviteJoinRedirect when configured', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    testPanelProvider()->inviteJoinRedirect('/wirechat-widget');

    $this->post(testPanelProvider()->inviteJoinRoute($invite->token))
        ->assertRedirect('/wirechat-widget')
        ->assertSessionHas('wirechat_pending_invite_token', $invite->token)
        ->assertSessionHas('wirechat_pending_invite_panel', testPanelProvider()->getId());
});

it('lets inviteJoinRedirect override the mount url for invite joins', function () {
    testPanelProvider()
        ->mountUrl('/app')
        ->inviteJoinRedirect('/wirechat-widget');

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
        ->assertRedirect('/wirechat-widget')
        ->assertSessionHas('wirechat_pending_invite_token', $invite->token)
        ->assertSessionHas('wirechat_pending_invite_panel', testPanelProvider()->getId());
});

it('redirects existing members from invite links to the mount url when chat routes are disabled', function () {
    testPanelProvider()
        ->registerRoutes(false)
        ->mountUrl('/app');

    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $this->actingAs($owner)
        ->get(testPanelProvider()->inviteRoute($invite->token))
        ->assertRedirect('/app')
        ->assertSessionHas('wirechat_pending_conversation_id', $conversation->id)
        ->assertSessionHas('wirechat_pending_conversation_panel', testPanelProvider()->getId());
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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('refuses an invite that is revoked after the lobby mounts', function () {
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

    $component = Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()]);

    $invite->revoke();

    $component
        ->call('proceed')
        ->assertDispatched('wirechat-toast', type: 'error');

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($invite->fresh()->usages)->toBe(0);
});

it('opens the joined group in widget mode from the invite lobby', function () {
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
        ->test(Lobby::class, [
            'token' => $invite->token,
            'panel' => testPanelProvider()->getId(),
            'widget' => true,
        ])
        ->call('proceed')
        ->assertNoRedirect()
        ->assertDispatched('open-chat')
        ->assertDispatched('closeWirechatModal');

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('opens the joined group internally from the invite lobby when panel routes are disabled', function () {
    testPanelProvider()->registerRoutes(false);

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
        ->test(Lobby::class, [
            'token' => $invite->token,
            'panel' => testPanelProvider()->getId(),
        ])
        ->call('proceed')
        ->assertNoRedirect()
        ->assertDispatched('open-chat', conversation: $conversation->id)
        ->assertDispatched('closeWirechatModal');

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('creates a join request from the in-app invite modal when approval is required', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['admins_must_approve_new_members' => true])->save();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertNoRedirect();

    $invite->refresh();

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->hasPendingJoinRequest($receiver))->toBeTrue()
        ->and(in_array($invite->usages, [null, 0], true))->toBeTrue();
});

it('lets invite users join immediately when admin approval is turned off after a pending request exists', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['admins_must_approve_new_members' => true])->save();

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertNoRedirect();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->hasPendingJoinRequest($receiver))->toBeTrue();

    $conversation->group->forceFill(['admins_must_approve_new_members' => false])->save();

    Livewire::actingAs($owner)
        ->test(GroupInfo::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSet('pendingJoinRequestsCount', 0)
        ->assertDontSee(__('wirechat::chat.group.join.requests.heading.label'));

    Livewire::actingAs($owner)
        ->test(Links::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.invite_link.labels.group_access_open'))
        ->assertDontSee(__('wirechat::chat.group.invite_link.labels.join_requests'));

    Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->assertSet('requiresApproval', false)
        ->assertSet('hasPendingJoinRequest', false)
        ->assertSee(__('wirechat::chat.group.join.lobby.labels.open_access'))
        ->assertSee(__('wirechat::chat.group.join.lobby.actions.join_group.label'))
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($conversation->group->joinRequests()->where('status', JoinRequestStatus::ACCEPTED)->count())->toBe(1)
        ->and($invite->usages)->toBe(1);
});

it('allows admin-removed users to request join from lobby when approval is required', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill([
        'type' => GroupType::PRIVATE,
        'admins_must_approve_new_members' => true,
    ])->save();

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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertNoRedirect();

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->hasPendingJoinRequest($receiver))->toBeTrue()
        ->and(in_array($invite->usages, [null, 0], true))->toBeTrue();
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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
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
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $participant->refresh();
    $invite->refresh();

    expect($participant->isRemovedByAdmin())->toBeFalse()
        ->and($participant->isBlockedByAdmin())->toBeFalse()
        ->and($receiver->belongsToConversation($conversation))->toBeTrue()
        ->and($invite->usages)->toBe(1);
});

it('keeps banned members from rejoining by invite until the ban is lifted', function () {
    $owner = User::factory()->create();
    $receiver = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['type' => GroupType::PUBLIC])->save();

    $participant = $conversation->addParticipant($receiver);
    $participant->banByAdmin($owner);

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertNoRedirect();

    $participant->refresh();
    $invite->refresh();

    expect($participant->isBannedByAdmin())->toBeTrue()
        ->and($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($invite->usages)->toBe(0);

    $participant->liftBanByAdmin();
    $participant->refresh();

    Livewire::actingAs($receiver)
        ->test(Lobby::class, ['token' => $invite->token, 'panel' => testPanelProvider()->getId()])
        ->call('proceed')
        ->assertRedirect(testPanelProvider()->chatRoute($conversation->id));

    $participant->refresh();
    $invite->refresh();

    expect($participant->isBannedByAdmin())->toBeFalse()
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
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('approve', $request->id)
        ->assertDispatched('wirechat-join-requests-banner-updated');

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
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('dismiss', $request->id);

    $invite->refresh();

    expect($receiver->belongsToConversation($conversation))->toBeFalse()
        ->and($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($request->fresh()->status)->toBe(JoinRequestStatus::DISMISSED)
        ->and($request->fresh()->reviewed_at)->not->toBeNull()
        ->and($invite->usages)->toBe(0);
});

it('renders bulk join request actions and load more controls for admins', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    collect(range(1, 11))->each(function (int $index) use ($conversation) {
        $conversation->group->requestToJoin(User::factory()->create(['name' => "Requester {$index}"]));
    });

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(trans_choice('wirechat::chat.group.join.requests.labels.count', 11, ['count' => 11]))
        ->assertSee(__('wirechat::chat.group.join.requests.actions.approve_all.label'))
        ->assertSee(__('wirechat::chat.group.join.requests.actions.dismiss_all.label'))
        ->assertSee(__('wirechat::chat.group.join.requests.actions.approve_all.confirmation_message'))
        ->assertSee(__('wirechat::chat.group.join.requests.actions.dismiss_all.confirmation_message'))
        ->assertSee(__('wirechat::chat.group.join.requests.actions.load_more.label'));
});

it('can load more join requests from the drawer', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $hiddenRequester = User::factory()->create(['name' => 'Hidden Requester']);
    $conversation->group->requestToJoin($hiddenRequester)->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    collect(range(1, 5))->each(function (int $index) use ($conversation) {
        $conversation->group->requestToJoin(User::factory()->create(['name' => "Visible Requester {$index}"]));
    });

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee('Hidden Requester')
        ->assertSee(__('wirechat::chat.group.join.requests.actions.load_more.label'))
        ->call('loadMore')
        ->assertSee('Hidden Requester');
});

it('keeps the join requests drawer interactive after approving a visible request', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $hiddenRequester = User::factory()->create(['name' => 'Pulled In Requester']);
    $conversation->group->requestToJoin($hiddenRequester)->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    $visibleRequests = collect(range(1, 5))->map(function (int $index) use ($conversation) {
        $requester = User::factory()->create(['name' => "Visible Approve {$index}"]);
        $conversation->group->requestToJoin($requester);

        return $conversation->group->pendingJoinRequests()->whereRequester($requester)->latest('id')->firstOrFail();
    });

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee('Pulled In Requester')
        ->call('approve', $visibleRequests->first()->id)
        ->assertSee(__('wirechat::chat.group.join.requests.heading.label'))
        ->assertSee('Pulled In Requester');
});

it('allows admins to approve all pending join requests from the drawer', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    $invite = $conversation->group->inviteLinks()->create([
        'panel_id' => testPanelProvider()->getId(),
        'created_by_id' => $owner->getKey(),
        'created_by_type' => $owner->getMorphClass(),
        'token' => Invite::generateToken(),
        'is_primary' => true,
    ]);

    $requesters = collect(range(1, 12))->map(function (int $index) use ($conversation, $invite) {
        $requester = User::factory()->create(['name' => "Approve {$index}"]);
        $conversation->group->requestToJoin($requester, $invite);

        return $requester;
    });

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('approveAll')
        ->assertDispatched('wirechat-join-requests-banner-updated');

    $invite->refresh();

    expect($requesters->every(fn ($requester) => $requester->belongsToConversation($conversation)))->toBeTrue()
        ->and($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($conversation->group->joinRequests()->where('status', JoinRequestStatus::ACCEPTED)->count())->toBe(12)
        ->and($invite->usages)->toBe(12);
});

it('allows admins to reject all pending join requests from the drawer', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');

    collect(range(1, 12))->each(function (int $index) use ($conversation) {
        $conversation->group->requestToJoin(User::factory()->create(['name' => "Dismiss {$index}"]));
    });

    Livewire::actingAs($owner)
        ->test(Requests::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->call('dismissAll');

    expect($conversation->group->pendingJoinRequests()->count())->toBe(0)
        ->and($conversation->participants()->count())->toBe(1)
        ->and($conversation->group->joinRequests()->where('status', JoinRequestStatus::DISMISSED)->count())->toBe(12);
});

it('shows the join request banner only to group admins', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $requester = User::factory()->create();

    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['admins_must_approve_new_members' => true])->save();
    $conversation->addParticipant($member);
    $conversation->group->requestToJoin($requester);

    Livewire::actingAs($owner)
        ->test(Chat::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.join.requests.labels.review'))
        ->assertSee(trans_choice('wirechat::chat.group.join.requests.labels.summary', 1, ['count' => 1]))
        ->assertSee('1');

    Livewire::actingAs($member)
        ->test(Chat::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertDontSee(__('wirechat::chat.group.join.requests.labels.review'));
});

it('pluralizes the join request banner summary for admins', function () {
    $owner = User::factory()->create();
    $conversation = $owner->createGroup('Test');
    $conversation->group->forceFill(['admins_must_approve_new_members' => true])->save();

    $conversation->group->requestToJoin(User::factory()->create());
    $conversation->group->requestToJoin(User::factory()->create());

    Livewire::actingAs($owner)
        ->test(Chat::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertSee(__('wirechat::chat.group.join.requests.labels.review'))
        ->assertSee('2')
        ->assertSee(trans_choice('wirechat::chat.group.join.requests.labels.summary', 2, ['count' => 2]));
});
