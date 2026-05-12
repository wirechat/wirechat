<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\Banned;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\Members;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\PastMembers;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(Members::class, ['conversation' => $conversation])
        ->assertStatus(401);
});

test('does not abort if user doest not belog to conversation', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->create(['type' => ConversationType::GROUP]);
    Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation])
        ->assertStatus(200);
});

test('aborts if conversation is private', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createConversationWith($receiver);
    Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation])
        ->assertStatus(403, 'This is a private conversation');
});

describe('presence test', function () {

    test(' Members title is set', function () {

        testPanelProvider()->maxGroupMembers(1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        // * since converstaion already have one user which is the auth then default is 1
        $request
            ->assertSee('Members');
    });

    test('close_modal_button_is_set_correctly', function () {

        testPanelProvider()->maxGroupMembers(1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        // * since converstaion already have one user which is the auth then default is 1
        $request->assertSeeHtml('dusk="close_modal_button"');
        $request->assertContainsBladeComponent('wirechat::actions.close-modal');

    });

    test('it loads members', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participants
        $conversation->addParticipant(User::factory()->create(['name' => 'John']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Lemon']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Cold']));

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
            ->assertSee('John')
            ->assertSee('Lemon')
            ->assertSee('Cold');
    });

    test('member action menu is layered above the sticky modal chrome', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $conversation->addParticipant(User::factory()->create(['name' => 'John']));

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        $request
            ->assertSeeHtml('x-anchor.bottom-end="$refs.button"')
            ->assertSeeHtml('class="z-20')
            ->assertSeeHtml('bg-[var(--wc-light-secondary)]');
    });

    test('member action menu uses a shared open state and closes on outside click', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $conversation->addParticipant(User::factory()->create(['name' => 'John']));

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        $request
            ->assertSeeHtml('x-data="{ openMemberMenu: null }"')
            ->assertSeeHtml('@click.outside="openMemberMenu = null"')
            ->assertSeeHtml('@click="openMemberMenu = openMemberMenu === memberMenuId ? null : memberMenuId"')
            ->assertSeeHtml('x-show="openMemberMenu === memberMenuId"');
    });

    test('it show label "You" if member in loop is auth user', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participants
        $conversation->addParticipant(User::factory()->create(['name' => 'John']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Lemon']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Cold']));

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        $request
            ->assertSeeText('You')
            ->assertSeeText('John')
            ->assertSeeText('Lemon')
            ->assertSeeText('Cold');

        $html = $request->html();

        preg_match_all('/>\s*You\s*<\/h6>/', $html, $youLabels);

        expect($youLabels[0])->toHaveCount(1);
    });

    test('it shows load more if user can load more thatn 10', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participants
        Participant::factory(20)->create(['conversation_id' => $conversation->id]);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
            ->assertSee('Load more');
    });

    test('it doesnt shows load more if user cannot load more than', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participants
        Participant::factory(5)->create(['conversation_id' => $conversation->id]);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->assertDontSee('Load more');
    });

    // testing for Owner
    test('Even if auth is owner, it doesnt show  "Dismiss As Admin" & "Make Admin" & "Remove" plus their wired methods wired if participant is owner in loop', function () {
        $auth = User::factory()->create(['name' => 'Participant']);
        $conversation = $auth->createGroup('My Group');

        // at this point only one user is present and is owner
        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertDontSee('Make Admin')
            ->assertPropertyNotWired('makeAdmin')
            ->assertDontSee('Dismiss As Admin')
            ->assertPropertyNotWired('dismissAdmin')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');

    });

    test('If auth is owner ,it shows  "Make Admin" & "Remove" plus their wired methods  if participant is NOT owner in loop', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $user = User::factory()->create(['name' => 'Participant']);
        $participant = $conversation->addParticipant($user);

        // at this point only one user is present and is owner
        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertSee('Make Admin')
            ->assertMethodWired('makeAdmin')

                 // here this one won't show since participnat is not admin
            ->assertDontSee('Dismiss As Admin')
            ->assertMethodNotWired('dismissAdmin')
            ->assertSee('Remove')
            ->assertMethodWired('removeFromGroup');

    });

    test('If auth is Not owner, it doesnt shows  "Dismiss As Admin" & "Make Admin"  plus their wired methods  for any participant n loop', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $notOwner = User::factory()->create(['name' => 'Participant']);
        $participant = $conversation->addParticipant($notOwner);

        // log in as $notOwner
        $request = Livewire::actingAs($notOwner)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertDontSee('Make Admin')
            ->assertPropertyNotWired('makeAdmin')
            ->assertDontSee('Dismiss As Admin')
            ->assertPropertyNotWired('dismissAdmin');
    });

    // testing for admin

    test('Admins can see "Remove" plus the wired method if participant role is Participant ', function () {
        $auth = User::factory()->create(['name' => 'owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'John cush']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::ADMIN]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleAdmin)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertSeeText('Remove')
            ->assertMethodWired('removeFromGroup');
    });

    test('Admins cannot see "Remove" plus the wired method if participant role is Admin ', function () {
        $auth = User::factory()->create(['name' => 'owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $participant = $conversation->addParticipant($roleParticipant);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // create Admin
        $roleAdmin = User::factory()->create(['name' => 'John cush']);
        $adminParticipant = $conversation->addParticipant($roleAdmin);
        $adminParticipant->update(['role' => ParticipantRole::ADMIN]);

        // create Admin2
        $roleAdmin2 = User::factory()->create(['name' => 'Bradly']);
        $adminParticipant2 = $conversation->addParticipant($roleAdmin2);
        $adminParticipant2->update(['role' => ParticipantRole::ADMIN]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleAdmin)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Bradly')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    test('Admins cannot see "Remove" plus the wired method if participant role is Owner ', function () {
        $auth = User::factory()->create(['name' => 'owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $participant = $conversation->addParticipant($roleParticipant);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // create Admin
        $roleAdmin = User::factory()->create(['name' => 'John cush']);
        $adminParticipant = $conversation->addParticipant($roleAdmin);
        $adminParticipant->update(['role' => ParticipantRole::PARTICIPANT]);

        // create Admin2
        $roleAdmin2 = User::factory()->create(['name' => 'Bradly']);
        $adminParticipant2 = $conversation->addParticipant($roleAdmin2);
        $adminParticipant2->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleAdmin)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'owner')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    test('Auth admin  cannot see "Remove" plus the wired method if participant own profile ', function () {
        $auth = User::factory()->create(['name' => 'owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $participant = $conversation->addParticipant($roleParticipant);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // create Admin
        $roleAdmin = User::factory()->create(['name' => 'John cush']);
        $adminParticipant = $conversation->addParticipant($roleAdmin);
        $adminParticipant->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleAdmin)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'John cush')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    // testing for Participants

    test('Participants can see "Dismiss As Admin" & "Make Admin" & "Remove" plus their wired methods if participant role is Participant in loop ', function () {
        $auth = User::factory()->create(['name' => 'owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Admin1']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertDontSee('Make Admin')
            ->assertPropertyNotWired('makeAdmin')
            ->assertDontSee('Dismiss As Admin')
            ->assertPropertyNotWired('dismissAdmin')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    test('Participants can see "Dismiss As Admin" & "Make Admin" & "Remove" plus their wired methods if participant role is Owner in loop ', function () {
        $auth = User::factory()->create(['name' => 'Owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Admin1']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Owner')
            ->assertDontSee('Make Admin')
            ->assertPropertyNotWired('makeAdmin')
            ->assertDontSee('Dismiss As Admin')
            ->assertPropertyNotWired('dismissAdmin')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    test('Participants can see "Dismiss As Admin" & "Make Admin" & "Remove" plus their wired methods if participant role is Admin in loop ', function () {
        $auth = User::factory()->create(['name' => 'Owner']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Admin1']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Admin1')
            ->assertDontSee('Make Admin')
            ->assertPropertyNotWired('makeAdmin')
            ->assertDontSee('Dismiss As Admin')
            ->assertPropertyNotWired('dismissAdmin')
            ->assertDontSee('Remove')
            ->assertPropertyNotWired('removeFromGroup');
    });

    /**
     * Testing roles title
     */
    test('it shows Owner title in loop', function () {
        $auth = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Admin1']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::PARTICIPANT]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'John')
            ->assertSee('Owner');
    });

    test('it shows Admin title in loop', function () {
        $auth = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Parcel']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::ADMIN]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Parcel')
            ->assertSee('Admin');
    });

    test('it wont show Role Admin or Owner is user is participant in loop', function () {
        $auth = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createGroup('My Group');

        // give him name participant
        $roleParticipant = User::factory()->create(['name' => 'Participant']);
        $conversation->addParticipant($roleParticipant);

        // create Admin and update role
        $roleAdmin = User::factory()->create(['name' => 'Parcel']);
        $participant = $conversation->addParticipant($roleAdmin);
        $participant->update(['role' => ParticipantRole::ADMIN]);

        // log in as $roleAdmin
        $request = Livewire::actingAs($roleParticipant)->test(Members::class, ['conversation' => $conversation]);
        $request
                // search so we can only get one user to test information
            ->set('search', 'Participant')
            ->assertDontSee('Admin')
            ->assertDontSee('Owner');

    });

    test('admins can see past and blocked member drawers', function () {
        $owner = User::factory()->create(['name' => 'Owner']);
        $admin = User::factory()->create(['name' => 'Admin']);

        $conversation = $owner->createGroup('My Group');
        $conversation->addParticipant($admin)->update(['role' => ParticipantRole::ADMIN]);

        Livewire::actingAs($admin)->test(Members::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertSee(__('wirechat::chat.group.members.actions.past_members.label'))
            ->assertSee(__('wirechat::chat.group.members.actions.banned_members.label'));
    });

});

describe('actions test', function () {

    test('Search can be filtered', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant(User::factory()->create(['name' => 'Micheal']));

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
            ->set('search', 'Mic')
            ->assertSee('Micheal');
    });

    describe('sendMessage: ', function () {
        test('it redirects to chat route and does not dispatch "closeWirechatModal" & "open-chat"  & "close-chat" event when componnet is not Wdiget route after creating conversation ', function () {
            $auth = User::factory()->create();
            $conversation = $auth->createGroup('My Group');

            // add participant
            $user = User::factory()->create(['name' => 'Micheal']);
            $participant = $conversation->addParticipant($user);

            $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
            $request
                ->call('sendMessage', $participant->id)
                ->assertRedirect(testPanelProvider()->chatRoute(2))
                ->assertNotDispatched('close-chat')
                ->assertNotDispatched('closeWirechatModal')
                ->assertNotDispatched('open-chat');
        });

        test('it dispatches "closeWirechatModal" & "open-chat"  & "close-chat" event and does not redirects to chat route and does not when componnet  is Wdiget route after creating conversation ', function () {
            $auth = User::factory()->create();
            $conversation = $auth->createGroup('My Group');

            // add participant
            $user = User::factory()->create(['name' => 'Micheal']);
            $participant = $conversation->addParticipant($user);

            $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation, 'widget' => true]);
            $request
                ->call('sendMessage', $participant->id)
                ->assertNoRedirect()
                ->assertDispatched('open-chat')
                ->assertDispatched('closeWirechatModal')
                ->assertNotDispatched('close-chat');

        });

        test('it create conversation between auth and user after calling sendMessage', function () {
            $auth = User::factory()->create();
            $conversation = $auth->createGroup('My Group');

            // add participant
            $user = User::factory()->create(['name' => 'Micheal']);
            $participant = $conversation->addParticipant($user);

            // assert before
            expect($auth->hasConversationWith($user))->toBe(false);

            $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
            $request
                ->call('sendMessage', $participant->id);

            // assert after
            expect($auth->hasConversationWith($user))->toBe(true);
        });
    });

    test('calling makeAdmin will make participan admin', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        // assert before
        expect($participant->isAdmin())->toBe(false);
        expect($user->isAdminIn($conversation->group))->toBe(false);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('makeAdmin', $participant->id);

        $participant = $participant->refresh();
        $user = $user->refresh();

        // assert after
        expect($participant->isAdmin())->toBe(true);
        expect($user->isAdminIn($conversation->group))->toBe(true);
    });

    test('calling dismiss as admin will remove admin role from participant', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        $participant->update(['role' => ParticipantRole::ADMIN]);

        $participant = $participant->refresh();
        $user = $user->refresh();

        // assert before
        expect($participant->isAdmin())->toBe(true);
        expect($user->isAdminIn($conversation->group))->toBe(true);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('dismissAdmin', $participant->id);

        $participant = $participant->refresh();
        $user = $user->refresh();

        // assert after
        expect($participant->isAdmin())->toBe(false);
        expect($user->isAdminIn($conversation->group))->toBe(false);
    });

    test('is aborts makeAdmin if participant is Owner ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $participant = $conversation->participants->first();

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('makeAdmin', $participant->id)
            ->assertStatus(403, 'Owner role cannot be changed');

        $participant = $participant->refresh();

        // make sure participant is still owner
        expect($participant->isOwner())->toBe(true);
    });

    test('is aborts dismissAdmin if participant is Owner ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $participant = $conversation->participants->first();

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('dismissAdmin', $participant->id)
            ->assertStatus(403, 'Owner role cannot be changed');

        $participant = $participant->refresh();

        // make sure participant is still owner
        expect($participant->isOwner())->toBe(true);
    });

    // test('it deletes participants model when removeFromGroup', function () {
    //     $auth = User::factory()->create();
    //     $conversation = $auth->createGroup('My Group');

    //     #add participant
    //     $user = User::factory()->create(['name' => 'Micheal']);
    //     $participant =  $conversation->addParticipant($user);

    //     #assert before
    //     expect($participant->participantable->belongsToConversation($conversation))->toBe(true);

    //     $request =  Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
    //     $request  ->call('removeFromGroup', $participant->id);

    //     #assert after
    //     expect($participant->participantable->belongsToConversation($conversation))->toBe(false);

    //  });

    test('it aborts  removeFromGroup if participant is Owner ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $participant = $conversation->participants->first();

        expect($participant->isOwner())->toBe(true);
        expect($participant->participantable->belongsToConversation($conversation))->toBe(true);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('removeFromGroup', $participant->id)
            ->assertStatus(403, 'Owner cannot be removed from group');

        $participant = $participant->refresh();

        // make sure participant is still owner
        expect($participant->isOwner())->toBe(true);
        expect($participant->participantable->belongsToConversation($conversation))->toBe(true);

    });

    test('it abort removeFromGroup if participant is does not belong to conversation ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $randomUser = User::factory()->create(['name' => 'Micheal']);

        $otherConversation = $randomUser->createConversationWith(User::factory()->create());

        $participant = $otherConversation->participants->first();

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('removeFromGroup', $participant->id)
            ->assertStatus(403, 'This user does not belong to conversation');

    });

    test('it abort removeFromGroup if auth is not admin in group ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $randomUser = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($randomUser);

        $userTobeRemoved = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($userTobeRemoved);

        $request = Livewire::actingAs($randomUser)->test(Members::class, ['conversation' => $conversation]);
        $request->call('removeFromGroup', $participant->id)
            ->assertStatus(403, 'You do not have permission to perform this action in this group. Only admins can proceed.');

    });

    test('it creates ations REMOVED_BY_ADMIN when a participant is  removed from  group', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        // assert before
        expect($participant->participantable->belongsToConversation($conversation))->toBe(true);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('removeFromGroup', $participant->id);

        // assert removed
        $removed = Action::where('actionable_id', $participant->id)
            ->where('actionable_type', Participant::class)
            ->where('type', Actions::REMOVED_BY_ADMIN)
            ->exists();
        expect($removed)->toBe(true);

        expect($participant->isRemovedByAdmin())->toBe(true);
    });

    test('removed participant->participantable is no longer a member of the conversation', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        // assert before
        expect($user->belongsToConversation($conversation))->toBe(true);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request->call('removeFromGroup', $participant->id);

        // assert removed
        $conversation = $conversation->refresh();

        expect($user->belongsToConversation($conversation))->toBe(false);
    });

    test('it removes participants   from blade when removeFromGroup is called', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        // assert before
        expect($participant->participantable->belongsToConversation($conversation))->toBe(true);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        // assert
        $request->assertSee($participant->wirechat_name);

        // action
        $request->call('removeFromGroup', $participant->id);

        // assert after
        $request->assertDontSee($participant->wirechat_name);

    });

    test('it dispatches livewire event "participantsCountUpdated" after removing from group', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $participant = $conversation->addParticipant($user);

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        // action
        $request->call('removeFromGroup', $participant->id);

        $request->assertDispatched('participantsCountUpdated');

    });

    test('calling blockMember creates a blocked past member and removes them from active members', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'Blocked User']);
        $participant = $conversation->addParticipant($user);

        Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->call('blockMember', $participant->id)
            ->assertDontSee($user->wirechat_name);

        $participant->refresh();

        expect($participant->isBlockedByAdmin())->toBeTrue()
            ->and($participant->isRemovedByAdmin())->toBeTrue()
            ->and($user->belongsToConversation($conversation->fresh()))->toBeFalse();
    });

    test('past members drawer shows left removed and blocked reasons', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $leftUser = User::factory()->create(['name' => 'Left User']);
        $removedUser = User::factory()->create(['name' => 'Removed User']);
        $blockedUser = User::factory()->create(['name' => 'Blocked User']);

        $conversation->addParticipant($leftUser)->exitConversation();
        $conversation->addParticipant($removedUser)->removeByAdmin($auth);
        $conversation->addParticipant($blockedUser)->blockByAdmin($auth);

        Livewire::actingAs($auth)->test(PastMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertSee($leftUser->wirechat_name)
            ->assertSee($removedUser->wirechat_name)
            ->assertSee($blockedUser->wirechat_name)
            ->assertSee(__('wirechat::chat.group.past_members.labels.reason_left'))
            ->assertSee(__('wirechat::chat.group.past_members.labels.reason_removed'))
            ->assertSee(__('wirechat::chat.group.past_members.labels.reason_blocked'));
    });

    test('banned members drawer can lift a ban without restoring active membership', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $blockedUser = User::factory()->create(['name' => 'Blocked User']);
        $participant = $conversation->addParticipant($blockedUser);
        $participant->blockByAdmin($auth);

        Livewire::actingAs($auth)->test(Banned::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertSee($blockedUser->wirechat_name)
            ->call('liftBan', $participant->id)
            ->assertDontSee($blockedUser->wirechat_name);

        $participant->refresh();

        expect($participant->isBlockedByAdmin())->toBeFalse()
            ->and($participant->hasExited())->toBeTrue()
            ->and($blockedUser->belongsToConversation($conversation->fresh()))->toBeFalse();
    });

    test('non-admins cannot access the past members drawer', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $conversation = $owner->createGroup('My Group');
        $conversation->addParticipant($member);

        Livewire::actingAs($member)->test(PastMembers::class, ['conversation' => $conversation, 'panel' => testPanelProvider()->getId()])
            ->assertStatus(403);
    });

});
