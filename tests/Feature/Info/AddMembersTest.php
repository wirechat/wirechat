<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Group\AddMembers;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(AddMembers::class, ['conversation' => $conversation])
        ->assertStatus(401);
});

test('aborts if user doest not belog to conversation', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->create();
    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

test('aborts if conversation is private', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createConversationWith($receiver);
    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation])
        ->assertStatus(403, 'Cannot add members to private conversation');
});

test('authenticaed user can access component ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = $auth->createGroup('My Group');

    Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation])
        ->assertStatus(200);
});

describe('presence test', function () {

    test('Add Members title is set', function () {

        Config::set('wirechat.max_group_members', 1000);

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        // * since converstaion already have one user which is the auth then default is 1
        $request
            ->assertSee('Add Members')
            ->assertSee('1 / 1000');

    });

    test('Create button is set and method wired', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
            ->assertSee('Save')
            ->assertMethodWired('save');
    });

});

describe('actions test', function () {

    test('Search can be filtered', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant(User::factory()->create(['name' => 'Micheal']));

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
        $request
            ->set('search', 'Mic')
            ->assertSee('Micheal');
    });

    test('toggleMember() method works correclty', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($user);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // attempt to add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertDontSee('Micheal');
    });

    test('it updated number when new members are added or removed', function () {

        Config::set('wirechat.max_group_members', 1000);
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // attempt to add member
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

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // first add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertSee('Micheal')
                // then remove memener
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertDontSee('Micheal');
    });

    test('existing member cannot be added to selectedMembers it aborts 403', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $conversation->addParticipant($user);

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // first add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->assertDontSee('Micheal')
            ->assertStatus(403, $user->display_name.' is already a member');
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

        $request = Livewire::actingAs($randomUser)->test(AddMembers::class, ['conversation' => $conversation]);
        $request->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
            ->assertStatus(403, "Cannot add {$participant->participantable->display_name} because they left the group");

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

        $request = Livewire::actingAs($randomUser)->test(AddMembers::class, ['conversation' => $conversation]);
        $request->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
            ->assertStatus(403, "Cannot add {$participant->participantable->display_name} because they were removed from the group by an Admin.");

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

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);
        $request->call('toggleMember', $userTobeRemoved->id, $userTobeRemoved->getMorphClass())
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

        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

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
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // attempt to add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $exists = $conversation->participants()->where('participantable_id', $user->id)->exists();
        expect($exists)->toBe(true);

    });

    test('it dispatches participantsCountUpdated event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // attempt to add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $request->assertDispatched('participantsCountUpdated');

    });

    test('it dispatches closeWireChatModal event after saving ', function () {
        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->create(['name' => 'Micheal']);
        $request = Livewire::actingAs($auth)->test(AddMembers::class, ['conversation' => $conversation]);

        $request
                // attempt to add member
            ->call('toggleMember', $user->id, $user->getMorphClass())
            ->call('save');

        $request->assertDispatched('closeWireChatModal');

    });

});
