<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\ChatBox;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;


///Auth checks 
it('checks if users is authenticated before loading chatbox', function () {
    Livewire::test(ChatBox::class)
        ->assertStatus(401);
});


test('authenticaed user can access chatbox ', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create(['sender_id'=>$auth->id]);
   // dd($conversation);
    Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
        ->assertStatus(200);
});


test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => 1])
        ->assertStatus(404);
});



test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
        ->assertStatus(403);
});


describe('Chatlist presence test: ', function () {



    test('it shows receiver name when conversation is loaded in chatbox', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);

        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);
       // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->assertSee("John");
    });
    


    test('it loads messages if they Exists in the conversation', function () {
        $auth = User::factory()->create();
        
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        //send messages
        $auth->sendMessageTo($receiver, message: 'How are you');
        $receiver->sendMessageTo($auth, message: 'i am good thanks');

        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->assertSee('How are you')
            ->assertSee('i am good thanks');

    });
    
    
    



});
