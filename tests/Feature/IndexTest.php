<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chats\Chats as Chatlist;
use Wirechat\Wirechat\Livewire\Pages\Chats;
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

    $response->assertSee('Choose a conversation to start messaging.');

});

test('it does not persist the chats sidebar shell on the chats index page', function () {
    $auth = User::factory()->create();

    $this->actingAs($auth)->get(testPanelProvider()->chatsRoute())
        ->assertStatus(200)
        ->assertDontSee('x-persist="chats"', false);
});
