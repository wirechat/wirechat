<?php

use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Group\Permissions;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(Permissions::class, ['conversation' => $conversation])
        ->assertStatus(401);
});

test('aborts if user doest not belog to conversation', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->create();
    Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

test('aborts if conversation is private', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createConversationWith($receiver);
    Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
        ->assertStatus(403, 'This feature is only available for groups');
});

test('it aborts if auth is not owner', function () {

    $auth = User::factory()->create(['id' => '345678']);
    $receiver = User::factory()->create();

    $conversation = $auth->createGroup('Test');
    $conversation->addParticipant($receiver);

    Livewire::actingAs($receiver)->test(Permissions::class, ['conversation' => $conversation])
        ->assertStatus(403, 'You do not have permission to edit group permissions');
});

describe('Presence check', function () {

    test('title is set', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
            ->assertSee('Permissions');
    });

    test('"Members can:" label is set', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
            ->assertSee('Members can:');
    });

    test('"Edit Group Information" label ,desciption is set and property wired', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
            ->assertSee(__('wirechat::chat.group.permisssions.actions.edit_group_information.label'))
            ->assertSee(__('wirechat::chat.group.permisssions.actions.edit_group_information.helper_text'))
            ->assertPropertyWired('allow_members_to_edit_group_info');
    });

    test('"Send messages "label is set and property wired', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
            ->assertSee(__('wirechat::chat.group.permisssions.actions.send_messages.label'))
            ->assertPropertyWired('allow_members_to_send_messages');
    });

    test('"Add other members" label is set and property wired', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation])
            ->assertSee(__('wirechat::chat.group.permisssions.actions.add_other_members.label'))
            ->assertPropertyWired('allow_members_to_add_others');
    });
});

describe('Actions', function () {

    test('it can toggle "allow_members_to_edit_group_info" permission', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        $request = Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation]);

        // toggle true
        $request->set('allow_members_to_edit_group_info', true);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_edit_group_info)->toBe(true);

        // toggle false
        $request->set('allow_members_to_edit_group_info', false);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_edit_group_info)->toBe(false);

    });

    test('it can toggle "allow_members_to_add_others" permission', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        $request = Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation]);

        // toggle true
        $request->set('allow_members_to_add_others', true);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_add_others)->toBe(true);

        // toggle false
        $request->set('allow_members_to_add_others', false);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_add_others)->toBe(false);

    });

    test('it can toggle "allow_members_to_send_messages" permission', function () {

        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create();

        $conversation = $auth->createGroup('Test');
        $conversation->addParticipant($receiver);

        $request = Livewire::actingAs($auth)->test(Permissions::class, ['conversation' => $conversation]);

        // toggle true
        $request->set('allow_members_to_send_messages', true);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_send_messages)->toBe(true);

        // toggle false
        $request->set('allow_members_to_send_messages', false);
        $group = $conversation->group;
        $group->refresh();
        expect($group->allow_members_to_send_messages)->toBe(false);

    });
});
