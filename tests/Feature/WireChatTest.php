<?php

use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Livewire\Widgets\WireChat;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(WireChat::class)
        ->assertStatus(401);
});

test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(WireChat::class);
    $response->assertSeeLivewire(Chats::class);

});

test('it doest not render livewire ChatBox component', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(WireChat::class);
    $response->assertDontSeeLivewire(Chat::class);

});

test('it shows label "Send private photos and messages" ', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(WireChat::class);
    $response->assertSee('Select a conversation to start messaging');

});

test('it renders Chat when "openChatWidget" event is selected ', function () {
    $auth = User::factory()->create();

    $conversation = $auth->createConversationWith(User::factory()->create());
    $response = Livewire::actingAs($auth)->test(WireChat::class);

    $response->assertDontSeeLivewire(Chat::class);

    $response->dispatch('openChatWidget', conversation: $conversation->id);

    // dd($response);
    $response->assertSeeLivewire(Chat::class);

});

test('it removes Chat when "closeChatWidget" event is selected ', function () {
    $auth = User::factory()->create();

    $conversation = $auth->createConversationWith(User::factory()->create());
    $response = Livewire::actingAs($auth)->test(WireChat::class);

    // assert
    $response->assertDontSeeLivewire(Chat::class);

    // open
    $response->dispatch('openChatWidget', conversation: $conversation->id);

    // assert
    $response->assertSeeLivewire(Chat::class);

    // open
    $response->dispatch('closeChatWidget');

    // assert
    $response->assertDontSeeLivewire(Chat::class);

});
