<?php

// /Presence test

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Livewire\New\Chat as NewChat;
use Workbench\App\Models\User as ModelsUser;

it('user must be authenticated', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::test(NewChat::class);

    $request->assertStatus(401);

});

// it('aborts 503 is feature not available', function () {
//     Config::set('wirechat.show_new_group_modal_button',false);
//     $auth = ModelsUser::factory()->create();

//     $request = Livewire::actingAs($auth)->test(NewChat::class);

//     $request->assertStatus(503,'The NewChat feature is currently unavailable.');

// });

it('Title is set ', function () {
    $auth = ModelsUser::factory()->create();
    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee('New Chat');

});

it('can filter users if search input is set', function () {
    $auth = ModelsUser::factory()->create();

    // create user
    ModelsUser::factory()->create(['name' => 'John']);

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->set('search', 'Joh')->assertSee('John');

});

test('search_users_field_is_set_correctly', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request
        ->assertSeeHtml('dusk="search_users_field"');

});

test('close_modal_button_is_set_correctly', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request
        ->assertSeeHtml('dusk="close_modal_button"');
    $request->assertContainsBladeComponent('wirechat::actions.close-modal');

});

it('shows New group if allowed', function () {
    testPanelProvider()->newChatAction()->newGroupAction();

    Config::set('wirechat.show_new_group_modal_button', true);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertSee('New group')
        ->assertSeeHtml('@dusk="open_new_group_modal_button"');

});

it('doesnt shows New group if not allowed', function () {

    testPanelProvider()->newGroupAction(false);
    $auth = ModelsUser::factory()->create();

    $request = Livewire::actingAs($auth)->test(NewChat::class);

    $request->assertDontSee('New group')
        ->assertDontSeeHtml('@dusk="open_new_group_modal_button"');

});

test('it shows new group button if user canCreateNewGroups==TRUE (email is verified)', function () {
    testPanelProvider()->newChatAction()->newGroupAction();

    $auth = ModelsUser::factory()->create(['email_verified_at' => now()]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request->assertSee('New group')
        ->assertSeeHtml('@dusk="open_new_group_modal_button"');

});

test('it doesnt show new group button if canCreateNewGroups==FALSE(email  NOT is verified)', function () {

    $auth = ModelsUser::factory()->create(['email_verified_at' => null]);
    $request = Livewire::actingAs($auth)->test(NewChat::class);
    $request->assertDontSee('New group')
        ->assertDontSeeHtml('@dusk="open_new_group_modal_button"');

});

describe('Creating conversation', function () {

    test('it created conversation when user is selected', function () {

        $auth = ModelsUser::factory()->create();

        // create user
        $otherUser = ModelsUser::factory()->create(['name' => 'John']);

        // assert user doenst have conversation
        expect($auth->hasConversationWith($otherUser))->toBeFalse();

        $request = Livewire::actingAs($auth)->test(NewChat::class);

        // search
        $request->set('search', 'Joh')->assertSee('John');

        // create conversation
        $request->call('createConversation', $otherUser->id, ModelsUser::class);

        expect($auth->hasConversationWith($otherUser))->toBeTrue();

    });

    test('it dispataches Livewire events "closeWirechatModal" after creating conversation', function () {

        $auth = ModelsUser::factory()->create();

        // create user
        $otherUser = ModelsUser::factory()->create(['name' => 'John']);

        $request = Livewire::actingAs($auth)->test(NewChat::class);

        // search
        $request->set('search', 'Joh')->assertSee('John');

        // create conversation
        $request->call('createConversation', $otherUser->id, ModelsUser::class);

        // assert redirect
        $request->assertDispatched('closeWirechatModal');

    });

    test('it redirects and does not dispatach Livewire events "open-chat" events after creating conversation if is not Widget', function () {

        $auth = ModelsUser::factory()->create();

        // create user
        $otherUser = ModelsUser::factory()->create(['name' => 'John']);

        $request = Livewire::actingAs($auth)->test(NewChat::class);

        // search
        $request->set('search', 'Joh')->assertSee('John');

        // create conversation
        $request->call('createConversation', $otherUser->id, ModelsUser::class);

        $conversation = $auth->conversations()->first();

        // assert redirect
        $request
            ->assertRedirect(testPanelProvider()->chatRoute($conversation->id))
            ->assertNotDispatched('open-chat');

    });

    test('it does not redirects but  dispataches Livewire events "open-chat" events after creating conversation if IS Widget', function () {

        $auth = ModelsUser::factory()->create();

        // create user
        $otherUser = ModelsUser::factory()->create(['name' => 'John']);

        $request = Livewire::actingAs($auth)->test(NewChat::class, ['widget' => true]);

        // search
        $request->set('search', 'Joh')->assertSee('John');

        // create conversation
        $request->call('createConversation', $otherUser->id, ModelsUser::class);

        $conversation = $auth->conversations()->first();

        // assert redirect

        $request
            ->assertNoRedirect()
            ->assertDispatched('open-chat');

    });

});
