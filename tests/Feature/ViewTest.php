<?php

use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chats\Chats as Chatlist;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\User;

it('redirects to login page if guest user tries to access chats page ', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->withParticipants([$auth])->create();
    $response = $this->get(route(WireChat::viewRouteName(), $conversation->id));

    $response->assertStatus(302);
    $response->assertRedirect(route('login')); // assuming 'login' is the route name for your login page
});

test('authenticaed user can access chats page ', function () {
    $auth = User::factory()->create();
    $user = User::factory()->create();

    $conversation = $auth->createConversationWith($user);
    // dd($conversation);
    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))
        ->assertStatus(200);

});

test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->withParticipants([$auth])->create();
    // dd($conversation);
    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))
        ->assertSeeLivewire(Chatlist::class);

});

test('it renders livewire Chat component', function () {
    $auth = User::factory()->create();
    $user = User::factory()->create();

    $conversation = $auth->createConversationWith($user);

    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))->assertSeeLivewire(Chat::class);

});

test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

    // $conversation =  Conversation::factory()->withParticipants([$auth])->create();
    // dd($conversation);
    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), 1))
        ->assertStatus(404);

});

test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();
    // dd($conversation);
    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id))
        ->assertStatus(403, 'Forbidden');

});

test('it marks messages as read when conversation is open ', function () {
    $auth = User::factory()->create();

    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

    // send messages to auth
    $receiver->sendMessageTo($auth, message: 'how is it going');
    $receiver->sendMessageTo($auth, message: 'i am good thanks');

    // confirm unread cound is 2 before user opens the chat
    expect($auth->getUnReadCount())->toBe(2);

    // visit page
    $this->actingAs($auth)->get(route(WireChat::viewRouteName(), $conversation->id));

    // noq assert that unread cound is now 0
    expect($auth->getUnReadCount())->toBe(0);

});
