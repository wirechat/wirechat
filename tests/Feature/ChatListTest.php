<?php

use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;


///Auth checks 
it('checks if users is authenticated before loading chatlist', function () {
    Livewire::test(ChatList::class)
        ->assertStatus(401);
});


test('authenticaed user can access chatlist ', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
        ->assertStatus(200);
});


///Content validations
it('has "chats label set in chatlist"', function () {
    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
        ->assertSee('Chat');
});


it('doesnt shows search field if search is disabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.user_search_allowed", false);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
        ->assertDontSee('Search');
});



it('shows search field if search is enabled in wirechat.config:tesiting Search placeholder', function () {

    Config::set("wirechat.user_search_allowed", true);

    $auth = User::factory()->create();
    Livewire::actingAs($auth)->test(ChatList::class)
        ->assertSee('Search');
});




describe('Chatlist', function () {


    it('it shows label "No conversations yet" items when user does not have chats', function () {

        $auth = User::factory()->create();

  


        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('No conversations yet');
    });


    it('it shows conversations items when user has chats', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);
        $user2 = User::factory()->create(['name' => 'iam user 2']);


        //create conversation with user1
        $auth->createConversationWith($user1);

        //create conversation with user2
        $auth->createConversationWith($user2);


        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('iam user 1')
            ->assertSee('iam user 2');
    });



    it('it shows last message and lable "you:" if it exists in chatlist', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');


        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('How are you doing')
            ->assertSee('You:');
    });

    it('it doesnt show label "you:" if last message doenst belong to auth', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
        $auth->createConversationWith($user1, message: 'How are you doing');
        sleep(1);
        //here we delay the create messsage so that we can NOT have both messages with the same timestamp
        //now let's send message to auth 
        $user1->sendMessageTo($auth, message: 'I am good');



        // dd($conversations,$messages);


        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('I am good') //see message
            // ->assertSee('How are you doing')//see message

            ->assertDontSee('You:'); //assert not visible
    });

    it('shows date/time message was created', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
       $conversation=  $auth->createConversationWith($user1);

        //manually create message so that we can adjust the time to 3 weeks ago

       Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $auth->id,
        'receiver_id' => $user1->id,
        'body' => "How are you doing"
        ]);


        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('1s');
    });

    it('it shows attatchment lable if message contains file or image', function () {

        $auth = User::factory()->create();

        $user1 = User::factory()->create(['name' => 'iam user 1']);

        //create conversation with user1
       $conversation=  $auth->createConversationWith($user1);

       $createdAttachment = Attachment::factory()->create();

        //manually create message so we can attach attachment id
       Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $auth->id,
        'receiver_id' => $user1->id,
        'attachment_id'=>$createdAttachment->id
        ]);



        Livewire::actingAs($auth)->test(ChatList::class)
            ->assertSee('📎 Attachment');
    });

});
