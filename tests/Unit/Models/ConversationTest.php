<?php

use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Workbench\App\Models\User;

describe('MarkAsRead',function(){


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


describe('getUnreadCountFor',function(){


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

 