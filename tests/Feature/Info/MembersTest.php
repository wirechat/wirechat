<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Chat\Group\Members;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;
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

        Config::set('wirechat.max_group_members', 1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);

        // * since converstaion already have one user which is the auth then default is 1
        $request
            ->assertSee('Members');
    });

    test('close_modal_button_is_set_correctly', function () {

        Config::set('wirechat.max_group_members', 1000);

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

    test('it show label "You" if member in loop is auth user', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participants
        $conversation->addParticipant(User::factory()->create(['name' => 'John']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Lemon']));
        $conversation->addParticipant(User::factory()->create(['name' => 'Cold']));
        $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
        $request
            ->assertSee('You');
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
        test('it redirects to chat route and does not dispatch "closeWireChatModal" & "open-chat"  & "close-chat" event when componnet is not Wdiget route after creating conversation ', function () {
            $auth = User::factory()->create();
            $conversation = $auth->createGroup('My Group');

            // add participant
            $user = User::factory()->create(['name' => 'Micheal']);
            $participant = $conversation->addParticipant($user);

            $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation]);
            $request
                ->call('sendMessage', $participant->id)
                ->assertRedirect(route(WireChat::viewRouteName(), 2))
                ->assertNotDispatched('close-chat')
                ->assertNotDispatched('closeWireChatModal')
                ->assertNotDispatched('open-chat');
        });

        test('it dispatches "closeWireChatModal" & "open-chat"  & "close-chat" event and does not redirects to chat route and does not when componnet  is Wdiget route after creating conversation ', function () {
            $auth = User::factory()->create();
            $conversation = $auth->createGroup('My Group');

            // add participant
            $user = User::factory()->create(['name' => 'Micheal']);
            $participant = $conversation->addParticipant($user);

            $request = Livewire::actingAs($auth)->test(Members::class, ['conversation' => $conversation, 'widget' => true]);
            $request
                ->call('sendMessage', $participant->id)
                ->assertNoRedirect(route(WireChat::viewRouteName(), 2))
                ->assertDispatched('open-chat')
                ->assertDispatched('closeWireChatModal')
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
        $request->assertSee($participant->display_name);

        // action
        $request->call('removeFromGroup', $participant->id);

        // assert after
        $request->assertDontSee($participant->display_name);

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

});
