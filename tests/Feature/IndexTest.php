<?php

use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chats\Chats as Chatlist;
use Namu\WireChat\Livewire\Pages\Chats;
use Workbench\App\Models\User;

// /Auth checks
it('it redirecdts to login page if guest user tries to access chats page ', function () {
    $response = $this->get(testPanelProvider()->chatsRoute());

    $response->assertStatus(302);
    $response->assertRedirect(route('login')); // assuming 'login' is the route name for your login page
});

test('authenticaed user can access chats page ', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(testPanelProvider()->chatsRoute());

    $response
        ->assertStatus(200);

});

test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(testPanelProvider()->chatsRoute());

    $response->assertSeeLivewire(Chatlist::class);

});

// test('it reders @wirechatAssets', function () {
//     $auth = User::factory()->create();

//    $response= Livewire::actingAs($auth)->test(Chats::class)->assertOK();
//    $response->assertContainsBladeComponent('wirechatAssets');

// });

test('it doest not render livewire ChatBox component', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(testPanelProvider()->chatsRoute());

    $response->assertDontSeeLivewire(Chat::class);

});

test('it shows label "Send private photos and messages" ', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(testPanelProvider()->chatsRoute());

    $response->assertSee('Select a conversation to start messaging');

});
