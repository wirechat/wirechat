<?php

use Namu\WireChat\Models\Conversation;
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

describe('AddUser()',function(){

    it('can add a user to a conversation', function () {

        $auth = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->addUser($auth);
        
        expect(count($conversation->users()->get()))->toBe(1);

    });

    it('does not add same user to conversation- aborts 422', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addUser($auth);

        $conversation->addUser($auth);

       // dd($conversation->users);

        expect(count($conversation->users()->get()))->toBe(1);

    })->throws(Exception::class,'User is already in the conversation.');

    it('does not add more than 2 users to a PRIVATE conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addUser($auth);
        $conversation->addUser(User::factory()->create());
        $conversation->addUser(User::factory()->create());

        expect($conversation->participants()->count())->toBe(2);

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

})->only();

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

 