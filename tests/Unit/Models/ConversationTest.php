<?php

use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;

describe('MarkAsRead()',function(){

    it('aborts with 401 is auth is not authenticated', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->withParticipants([$auth])->create();


        $conversation->markAsRead();

         
    })->throws(Exception::class);

    it('marks messages as read', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation
        $conversation = Conversation::factory() ->withParticipants([$auth,$receiver])->create();


        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        //send message to auth
        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');


        //Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(2);


        //assert returns zero(0) when messages are marked as read
        $conversation->markAsRead();
        expect($auth->getUnreadCount($conversation))->toBe(0);

    });
    
});

describe('AddParticipant()',function(){

    it('can add a participants to a conversation', function () {

        $auth = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->addParticipant($auth);
        
        expect(count($conversation->participants()->get()))->toBe(1);

    });

    it('does not add same participant to conversation- aborts 422', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);

        $conversation->addParticipant($auth);

       // dd($conversation->users);

        expect(count($conversation->participants()->get()))->toBe(1);

    })->throws(Exception::class,'Participant is already in the conversation.');

    it('does not add more than 2 participants to a PRIVATE conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        expect($conversation->participants()->count())->toBe(2);

    })->throws(Exception::class);

   
    it('can add more than 2 participants if it is a  GROUP conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type'=>ConversationType::GROUP]);

       // dd($conversation);
        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());


        expect($conversation->participants()->count())->toBe(4);

    });


});

describe('getUnreadCountFor()',function(){

    it('returns unread messages count for the specified user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '6');
        $receiver->sendMessageTo($auth, message: '7');

        //Assert number of unread messages for $auth
        expect($conversation->getUnreadCountFor($auth))->toBe(4);

    });

});


describe('deleting for me',function(){


    it('load all conversations if not deleted', function () {
        $auth = User::factory()->create();

        //Authenticate
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        //send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $auth->sendMessageTo(User::factory()->create(),'hello-2');
        $auth->sendMessageTo(User::factory()->create(),'hello-3');

        //assert count

       /// dd($messages);
        expect($auth->conversations->count())->toBe(3);

    });


    it('aborts if user is not authenticated before deletingForMe', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver,'hello');
 
        //delete messages
        $conversation->deleteForMe();

         //assert new count
         expect($conversation->count())->toBe(1);

    })->throws(Exception::class);



    it('aborts if user does not belong to conversation before deletingForMe', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver,'hello');
 
        //Authenticate
        $this->actingAs(User::factory()->create());

        //delete messages
        $conversation->deleteForMe();

         //assert new count
         expect($conversation->count())->toBe(1);

    })->throws(Exception::class);


    it('deletes and does not load deleted conversations(for me)', function () {
        
        //Dusk to 
        
        $auth = User::factory()->create();


        //Send to receiver
        $conversation1=  $auth->sendMessageTo(User::factory()->create(),'hello-1')->conversation;
        $conversation2=  $auth->sendMessageTo(User::factory()->create(),'hello-2')->conversation;
        $conversation3=  $auth->sendMessageTo(User::factory()->create(),'hello-3')->conversation;

        //Assert Count
        expect($auth->conversations->count())->toBe(3);

        //Authenticate
        //$auth->refresh();
        
        $this->actingAs($auth);

        //Delete Conversation
        $conversation3->deleteForMe();

        //conversations
        expect($auth->conversations()->count())->toBe(2);

    });


    it('triggers delete messages for me when conversation is deleted', function () {
        
        //Dusk to 
        
        $auth = User::factory()->create();
        $receiver = User::factory()->create();



        //Send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');
        $conversation=  $auth->sendMessageTo($receiver,'hello-4')->conversation;

        //Assert Count
        expect($conversation->messages()->count())->toBe(4);

        //Authenticate
        //$auth->refresh();
        
        $this->actingAs($auth);

        //Delete Conversation
        $conversation->deleteForMe();

        $this->actingAs($auth);

        expect($conversation->messages()->count())->toBe(0);

    });

})->only();

describe('deleting permanently()',function(){

    it('deletes all it\'s participants when conversation is deleted', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type'=>ConversationType::GROUP]);

       // dd($conversation);
        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        //assert available 
        expect($conversation->participants()->count())->toBe(4);

        //delete conversation 

        $conversation->delete();


        expect($conversation->participants()->count())->toBe(0);

    });

    it('deletes all messages when converstion is deleted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation = Conversation::factory()->withParticipants([$receiver,$auth])->create(['type'=>ConversationType::PRIVATE]);

        //dd($conversation);
        $auth->sendMessageTo($receiver,'hello');
        $auth->sendMessageTo($receiver,'hello');
        $auth->sendMessageTo($receiver,'hello');
        $auth->sendMessageTo($receiver,'hello');
        $auth->sendMessageTo($receiver,'hello');

        //assert available 
        expect($conversation->messages()->count())->toBe(5);

        //delete conversation 
        $conversation->delete();


        expect($conversation->messages()->count())->toBe(0);

    });
});


 