<?php

use Namu\WireChat\Livewire\Chat\ChatBox;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Workbench\App\Models\User;

it('it redirecdts to login page if guest user tries to access chats page ', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
    $response = $this->get(route("wirechat.chat",$conversation->id));

    $response->assertStatus(302);
    $response->assertRedirect(route('login')); // assuming 'login' is the route name for your login page
});


test('authenticaed user can access chats page ', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
   // dd($conversation);
   $this->actingAs($auth)->get(route("wirechat.chat",$conversation->id))
        ->assertStatus(200);
    
});




test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
   // dd($conversation);
   $this->actingAs($auth)->get(route("wirechat.chat",$conversation->id))
   ->assertSeeLivewire(ChatList::class);
    
});


test('it renders livewire ChatBox component', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
   // dd($conversation);
   $this->actingAs($auth)->get(route("wirechat.chat",$conversation->id))
   ->assertSeeLivewire(ChatBox::class);
    
});

test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

   // $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
   // dd($conversation);
   $this->actingAs($auth)->get(route("wirechat.chat",1))
   ->assertStatus(404);
    
});

test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();
   // dd($conversation);
   $this->actingAs($auth)->get(route("wirechat.chat",$conversation->id))
   ->assertStatus(403);
    
});

test('it marks unread messages as read when conversation is open ', function () {
    $auth = User::factory()->create();

    $receiver = User::factory()->create(['name'=>'John']);
    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


    //send messages to auth
    $receiver->sendMessageTo($auth, message: 'how is it going');
    $receiver->sendMessageTo($auth, message: 'i am good thanks');


    ///confirm unread cound is 2 before user opens the chat
    expect($auth->getUnReadCount())->toBe(2);

    //visit page 
    $this->actingAs($auth)->get(route("wirechat.chat",$conversation->id));


    ///noq assert that unread cound is now 0 
    expect($auth->getUnReadCount())->toBe(0);

    
});



