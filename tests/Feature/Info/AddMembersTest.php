<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\AddMembers;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Services\WirechatSettingsManager;
use Workbench\App\Models\User;

beforeEach(function () {
    testPanelProvider()->groupInvitations(true);
});

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(401);
});

test('aborts if user doest not belog to conversation', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->create();
    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);
});

test('aborts if conversation is private', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createConversationWith($receiver);
    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403, 'Cannot add members to private conversation');
});

test('authenticaed user can access component ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = $auth->createGroup('My Group');

    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200);
});

test('participant with add-members permission can access component', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = $owner->createGroup('My Group');
    $conversation->group->allow_members_to_add_others = true;
    $conversation->group->save();

    $participant = $conversation->addParticipant($member);
    $participant->role = ParticipantRole::PARTICIPANT;
    $participant->save();

    Livewire::actingAs($member)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(200);
});

test('participant without add-members authority cannot access component', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = $owner->createGroup('My Group');
    $conversation->group->allow_members_to_add_others = false;
    $conversation->group->save();

    $participant = $conversation->addParticipant($member);
    $participant->role = ParticipantRole::PARTICIPANT;
    $participant->save();

    Livewire::actingAs($member)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
        ->assertStatus(403);
});

describe('presence test', function () {

    test('Add Members title is set', function () {

        testPanelProvider()->maxGroupMembers(1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        // * since converstaion already have one user which is the auth then default is 1
        $request
            ->assertSee('Add Members')
            ->assertSee('1 / 1000');

    });

    test('Create button is set and method wired', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
            ->assertSee('Save')
            ->assertMethodWired('save');
    });

    test('admins see the copy invite link shortcut', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $conversation = $owner->createGroup('My Group');
        $participant = $conversation->addParticipant($admin);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        Livewire::actingAs($admin)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertSee(__('wirechat::chat.group.add_members.actions.invite_via_link.label'))
            ->assertSeeHtml('copyWithSelection')
            ->assertSeeHtml('window.navigator.clipboard.writeText(value)')
            ->assertSeeHtml('copyWithClipboard().then((copied) => {')
            ->assertSeeHtml('if (copied || copyWithSelection())');
    });

    test('participants with add-members permission do not see the copy invite link shortcut without invite-link permission', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $conversation = $owner->createGroup('My Group');
        $conversation->group->allow_members_to_add_others = true;
        $conversation->group->save();

        $participant = $conversation->addParticipant($member);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($member)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertDontSee(__('wirechat::chat.group.add_members.actions.invite_via_link.label'))
            ->assertDontSeeHtml('copyWithSelection');
    });

    test('participants with add-members and invite-link permissions see the copy invite link shortcut', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $conversation = $owner->createGroup('My Group');
        $conversation->group->allow_members_to_add_others = true;
        $conversation->group->allow_members_to_invite_others_via_link = true;
        $conversation->group->save();

        $participant = $conversation->addParticipant($member);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        Livewire::actingAs($member)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertSee(__('wirechat::chat.group.add_members.actions.invite_via_link.label'))
            ->assertSeeHtml('copyWithSelection')
            ->assertSeeHtml('window.navigator.clipboard.writeText(value)')
            ->assertSeeHtml('copyWithClipboard().then((copied) => {')
            ->assertSeeHtml('if (copied || copyWithSelection())');
    });

});

describe('actions test', function () {

    test('Search can be filtered', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create([
            'name' => 'Micheal',
            'email' => 'micheal.add-members@example.test',
        ]);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);
        $request
            ->set('search', 'Mic')
            ->assertSee('Micheal')
            ->assertSee($user->wirechat_subtitle)
            ->assertSeeHtml('class="min-w-0 flex-1"');
    });

    test('users who disallow group adds are hidden from add members search', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $user = User::factory()->create(['name' => 'Micheal']);

        app(WirechatSettingsManager::class)->updateFor($user, ['groups_can_add_me' => false]);

        Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Mic')
            ->assertDontSee('Micheal');
    });

    test('toggleMember() method works correclty', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($user);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // attempt to add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSet('selectedMembers', collect())
            ->assertDispatched('wirechat-toast', type: 'error');
    });

    test('toggleMember() rejects users who disallow group adds', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $user = User::factory()->create(['name' => 'Micheal']);

        app(WirechatSettingsManager::class)->updateFor($user, ['groups_can_add_me' => false]);

        Livewire::actingAs($auth)
            ->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertDispatched('wirechat-toast', type: 'error', message: __('wirechat::chat.group.add_members.messages.group_add_privacy_denied', ['member' => $user->wirechat_name]));
    });

    test('toggleMember() ignores tampered classes outside the current panel search results', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $user = User::factory()->create(['name' => 'Micheal']);

        Livewire::actingAs($auth)
            ->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, Conversation::class)
            ->assertSet('selectedMembers', collect());
    });

    test('it updated number when new members are added or removed', function () {

        testPanelProvider()->maxGroupMembers(1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // attempt to add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSee('2 / 1000')
                // attempt to remove member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSee('1 / 1000');
    });

    test('toggleMember() - can add and removing members from selectedMembers list', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // first add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSee('Micheal')
                // then remove memener
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->set('search', '')
            ->assertDontSee('Micheal');
    });

    test('toggleMember() removes selected members after the search query changes', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $user = User::factory()->create(['name' => 'Micheal']);
        User::factory()->create(['name' => 'Jessica']);

        Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSee('Micheal')
            ->assertSet('newTotalCount', 2)
            ->set('search', 'Jessica')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSet('selectedMembers', collect())
            ->assertSet('newTotalCount', 1)
            ->assertDontSee('Micheal');
    });

    test('existing member cannot be added to selectedMembers it aborts 403', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($user);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // first add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSet('selectedMembers', collect())
            ->assertDispatched('wirechat-toast', type: 'error');
    });

    test('it aborts if admin tries to add a member who exited the group', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $randomUser = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($randomUser);

        $userTobeRemoved = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($userTobeRemoved);

        $participant->exitConversation();

        $request = Livewire::actingAs($randomUser)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);
        $request->set('search', 'Micheal')
            ->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
            ->assertDispatched('wirechat-toast', type: 'error');

    });

    test('it aborts if NON-admin tries to add a member removed by admin', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $randomUser = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($randomUser);

        $userTobeRemoved = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($userTobeRemoved);

        // remove by auth
        $participant->removeByAdmin($auth);

        $request = Livewire::actingAs($randomUser)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);
        $request->set('search', 'Micheal')
            ->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
            ->assertDispatched('wirechat-toast', type: 'error');

    });

    test('it shows warning toast and does not select banned past member', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $blockedUser = User::factory()->create(['name' => 'Blocked User']);
        $participant = $conversation->addParticipant($blockedUser);
        $participant->banByAdmin($auth);

        Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Blocked User')
            ->call('toggleMember', $blockedUser->id, $blockedUser->getMorphClass())
            ->assertStatus(200)
            ->assertDispatched('wirechat-toast', type: 'warning')
            ->assertSet('selectedMembers', collect());
    });

    test('it does not abort if ADMIN tries to add a member removed by admin', function () {
        $auth = User::factory()->create(['name' => 'auth User']);
        $conversation = $auth->createGroup('My Group');

        $userTobeRemoved = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($userTobeRemoved);

        // assert new count is 2
        expect($conversation->participants()->count())->toBe(2);

        // remove by auth
        $participant->removeByAdmin($auth);

        // assert new count is now 1

        expect($conversation->participants()->count())->toBe(1);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);
        $request->set('search', 'Micheal')
            ->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
            ->call('save')
            ->assertStatus(200);

        // assert new count is back to  2
        expect($conversation->participants()->count())->toBe(2);

    });

    test('it shows "Already added to group" if already added to group', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant(User::factory()->create(['name' => 'John']));

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // first add member
            ->set('search', 'John')
                // user
            ->assertSee('Already added to group');
    });

    test('it saved new members to database ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // attempt to add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $exists = $conversation->participants()->where('participantable_id', $user->id)->exists();
        expect($exists)->toBe(true);

    });

    test('save() rejects stale selected members who later disallow group adds', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');
        $user = User::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)
            ->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass());

        app(WirechatSettingsManager::class)->updateFor($user, ['groups_can_add_me' => false]);

        $request
            ->call('save')
            ->assertDispatched('wirechat-toast', type: 'error', message: __('wirechat::chat.group.add_members.messages.group_add_privacy_denied', ['member' => $user->wirechat_name]));

        $exists = $conversation->participants()->where('participantable_id', $user->id)->exists();
        expect($exists)->toBeFalse();
    });

    test('it dispatches participantsCountUpdated event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // attempt to add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $request->assertDispatched('participantsCountUpdated');

    });

    test('it dispatches closeWirechatModal event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()]);

        $request
                // attempt to add member
            ->set('search', 'Micheal')
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $request->assertDispatched('closeWirechatModal');

    });

});
