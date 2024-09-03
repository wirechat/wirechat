<?php

use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Workbench\App\Models\User;

describe('Getting conversations',function(){


    it('returns  correct conversations belonging to user', function () {

        $auth = User::factory()->create();

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

        //assert conversation belongs to user
        foreach ($conversations as $key => $conversation) {

           $conversationExists=  $conversation->participants()->where('user_id',$auth->id)->exists();
           expect($conversationExists)->toBe(true);
        }
    });


    it('returns correct number of conversations for user', function () {

        $auth = User::factory()->create();

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

        //assert count
        expect(count($conversations))->toBe(3);

    });
    
});

describe('createConversationWith() ',function(){


    it('creates & returns created conversation when createConversationWith is called', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //assert 
        $conversation =$auth->createConversationWith($receiver);
        //assert 
        expect($conversation)->not->toBe(null);

        expect($conversation)->toBeInstanceOf(Conversation::class);
        //check database
        $conversation= Conversation::first();
        expect($conversation)->not->toBe(null);

    });

    it('creates 2 participants for conversation when created', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        //check database
        expect(count($conversation->participants))->toBe(2);
         
        //assert partipant $auth
         expect( $conversation->participants()->where('user_id',$auth->id)->exists())->toBe(true);

        //assert partipant $receiver
        expect( $conversation->participants()->where('user_id',$receiver->id)->exists())->toBe(true);


    });


    test('The created conversation must be PRIVATE', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        //check database
        expect($conversation->type)->toBe(ConversationType::PRIVATE);


    });

    it('does not create double conversations if conversation already exists between two users', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation attempt 1
        $conversation1 = $auth->createConversationWith($receiver);

        //create conversation attempt 2
        $conversation2 = $receiver->createConversationWith($auth);
        expect($conversation2->id)->toBe($conversation1->id);


        //assert $auth and $receiver only has one conversation 

        expect(count($auth->conversations))->toBe(1);

        expect(count($receiver->conversations))->toBe(1);



    });


    it('it creates message model when a message is passed ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        


        //create conversation
        $conversation = $auth->createConversationWith($receiver,message:'Hello');


        //assert
        expect(count($conversation->messages))->toBe(1);


    });




});

describe('sendMessageTo() ',function(){



    it('creates new conversation if it didn\'t alrady exist between the two users ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $message =$auth->sendMessageTo($receiver,'hello');
        //assert 
       

        $conversation= Conversation::first();

        //assert conversation id
        expect($conversation)->not->toBe(null);


        //assert conversation id
        expect($message->conversation_id)->toBe($conversation->id);


        
    });
    
    it('creates & returns created message when sendMessageTo is called', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $message =$auth->sendMessageTo($receiver,'hello');
        //assert 
        expect($message)->not->toBe(null);

        expect($message)->toBeInstanceOf(Message::class);
        //check database
        $conversation= Conversation::first();
        expect($conversation)->not->toBe(null);

    });


    it('saves created message to database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $message =$auth->sendMessageTo($receiver,'hello');

        //assert 
        expect($message)->not->toBe(null);

        //check database
        $messageFromDB= Message::find($message->id);

        //assert content
        expect($messageFromDB->id)->toBe($message->id);
        expect($messageFromDB->body)->toBe($message->body);

    });



    test('created message belongs to correct conversation ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        //send message
     
        $message =$auth->sendMessageTo($receiver,'hello');


        expect($message->conversation_id )->toBe($conversation->id);


        

    });


    it('updates the conversation updated_at field when message is created', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        //we use sleep to avoid timestamps being the same during test
        sleep(1);

        $auth->sendMessageTo($receiver,'hello');

        $conversationFromDB= Conversation::find($conversation->id);

        expect($conversationFromDB->updated_at)->toBeGreaterThan($conversation->updated_at);


        

    });




});

describe('belongsToConversation() ',function(){


    it('returns false if user does not belong to conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation= $auth->createConversationWith($receiver);
        


        //create conversation
        $randomUser= User::factory()->create();


        //assert
        expect($randomUser->belongsToConversation($conversation))->toBe(false);


    });



    it('returns true if user belongs to conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation= $auth->createConversationWith($receiver);
        
        //assert
        expect($auth->belongsToConversation($conversation))->toBe(true);


    });


}) ;

describe('hasConversationWith() ',function(){

    it('returns false if user does not have conversation with another user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation= $auth->createConversationWith($receiver);
        


        //create conversation
        $randomUser= User::factory()->create();


        //assert
        expect($randomUser->hasConversationWith($auth))->toBe(false);


    });

    it('returns true if user has conversation with another user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation=  $auth->createConversationWith($receiver);


        //assert
        expect($receiver->hasConversationWith($auth))->toBe(true);


    });

});

describe('getUnreadCount()',function(){



    it('returns correct number of unreadMessages if Conversation model is passed', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation
        $conversation = Conversation::factory() ->withParticipants([$auth,$receiver])->create();


        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');


        //send message to auth
        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');


        //Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBe(2);


 

    });

    it('returns all unread count if Conversation model is not passed', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        #create first conversation and receiver messages
        Conversation::factory()->withParticipants([$auth,$receiver])->create();
        $receiver->sendMessageTo($auth, message: '1');
        $receiver->sendMessageTo($auth, message: '1');

        #create new conversation and receive messages
        $receiver2 = User::factory()->create();
        Conversation::factory()->withParticipants([$auth,$receiver2])->create();
        $receiver2->sendMessageTo($auth, message: 'new 1');
        $receiver2->sendMessageTo($auth, message: 'new 2');
        $receiver2->sendMessageTo($auth, message: 'new 3');


        //Assert number of total unread  count for $auth
        expect($auth->getUnreadCount())->toBe(5);


    });


    it('it returns a numeric value', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation
        $conversation = Conversation::factory() ->withParticipants([$auth,$receiver])->create();


        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');


        //send message to auth
        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');

        //Assert number of unread messages for $auth
        expect($auth->getUnreadCount($conversation))->toBeNumeric();

    });

});
