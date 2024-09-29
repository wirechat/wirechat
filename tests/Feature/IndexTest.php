<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\ChatBox;
use Namu\WireChat\Livewire\Chat\Chats as Chatlist;
use Namu\WireChat\Livewire\Chat\Chats;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;


///Auth checks 
it('it redirecdts to login page if guest user tries to access chats page ', function () {
    $response = $this->get(route("wirechat"));

    $response->assertStatus(302);
    $response->assertRedirect(route('login')); // assuming 'login' is the route name for your login page
});


test('authenticaed user can access chats page ', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(route("wirechat"));
 
     $response
     ->assertStatus(200);
    
});


test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(route("wirechat"));
 
     $response ->assertSeeLivewire(Chatlist::class);
    
});


test('it doest not render livewire ChatBox component', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(route("wirechat"));
 
     $response ->assertDontSeeLivewire(ChatBox::class);
    
});


test('it shows label "Send private photos and messages" ', function () {
    $auth = User::factory()->create();
    $response = $this->withoutExceptionHandling()->actingAs($auth)->get(route("wirechat"));
 
     $response ->assertSee("Send private  photos and messages");
    
});
 

