<?php

use Illuminate\Http\UploadedFile;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Enums\RoomType;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Group;
use Workbench\App\Models\User;

describe('Getting conversations',function(){


    it('returns  correct conversations belonging to user', function () {

        $auth = User::factory()->create();
      //  dd($auth);

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

      //  dd($conversations);
        //assert conversation belongs to user
        foreach ($conversations as $key => $conversation) {

           $conversationExists=  $conversation->participants()
                                            ->where('participantable_id',$auth->id)
                                            ->where('participantable_type',get_class($auth))
                                            ->exists();
           expect($conversationExists)->toBe(true);
        }
    });


    it('returns correct number of conversations for user', function () {

        $auth = User::factory()->create();

        $conversations = Conversation::factory(3)->withParticipants([$auth])->create();

        //assert count
        expect(count($conversations))->toBe(3);

    });
    
}) ;

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

        // Eager load the participants relationship


        $conversation= Conversation::find($conversation->id);

        //check database
        expect(count($conversation->participants))->toBe(2);

    });

    it('It saves role as owner for both paritipants', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        // Eager load the participants relationship


        $conversation= Conversation::find($conversation->id);

        $bothAreOwners = false;


        foreach ($conversation->participants as $key => $value) {

            $bothAreOwners = $value->role == ParticipantRole::OWNER;
            # code...
        }

        //check database
        expect($bothAreOwners)->toBe(true);

    });

    it('saved correct type and id in participants model', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //create conversation
        $conversation = $auth->createConversationWith($receiver);

        // Eager load the participants relationship

        $conversation= Conversation::find($conversation->id);

        //assert partipant $auth
         expect( $conversation->participants()
                              ->where('participantable_id',$auth->id)
                              ->where('participantable_type',get_class($auth))
                              ->exists())->toBe(true);

        //assert partipant $receiver
        expect( $conversation->participants() 
                             ->where('participantable_id',$receiver->id)
                             ->where('participantable_type',get_class($receiver))
                             ->exists())->toBe(true);


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

    it('user can create conversation with themselves', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //create conversation
        $conversation = $auth->createConversationWith($auth,message:'Hello');


       // Eager load the participants relationship

       $conversation= Conversation::find($conversation->id);

       $participants = $conversation->participants;

       expect(count($participants))->toBe(2);

       
       foreach ($participants as $key => $participant) {

        expect($participant->participantable_id)->toBe("$auth->id");
        expect($participant->participantable_type)->toBe(get_class($auth));

       }


    });

    it('it does not create duplicate conversation is conversation already exists between same user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //create conversation
        $conversation = $auth->createConversationWith($auth,message:'Hello');
        $conversation = $auth->createConversationWith($auth);
        $conversation = $auth->createConversationWith($auth);


       // Eager load the participants relationship
        expect(count(Conversation::all()))->toBe(1);
 


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



describe('createGroup',function(){

    it('it creates conversation in database', function () {

        $auth = User::factory()->create();
        $conversation= $auth->createGroup(name:'New group',description:'description');

        //assert
        expect(Conversation::find($conversation))->not->toBe(null);

    });



    it('it creates room in database', function () {

        $auth = User::factory()->create();

        $conversation= $auth->createGroup(name:'New group',description:'description');

        $group = $conversation->group;

        //assert
        expect(Group::find($group->id)->id)->toBe($group->id);


    });


    it('it saves room data if correctly', function () {

        $auth = User::factory()->create();
        $photo =UploadedFile::fake()->create('photo.png');
        $conversation= $auth->createGroup(name:'New group',description:'description',photo:$photo);

        $group = $conversation->group;
        //assert

        expect($group->name)->toBe('New group');
        expect($group->description)->toBe('description');
        expect($group->cover)->not->toBe(null);


    });



    it('creates participant as owner to group', function () {

        $auth = User::factory()->create();

        $conversation= $auth->createGroup(name:'New group',description:'description');

        $participant = $conversation->participants()->first();


        //assert
        expect($participant->participantable_id)->toEqual($auth->id);
        


    });


});


describe('Exit conversation',function(){



    test('Owner cannot exit conversation', function () {

        $auth = User::factory()->create();
        $conversation= $auth->createGroup(name:'New group',description:'description');

        //assert
        expect($auth->exitConversation($conversation))->toBe(false);

    })->throws(Exception::class,'Owner cannot exit conversation');



    test('User cannot exit from private conversation', function () {

        $auth = User::factory()->create();
        $conversation= $auth->createConversationWith(User::factory()->create());

        //assert
        expect($auth->exitConversation($conversation))->toBe(false);

    })->throws(Exception::class,'Participant cannot exited a private conversation');





    it('marks participant exited_at table when user exits conversation', function () {

        $auth = User::factory()->create();

        $conversation= $auth->createGroup(name:'New group',description:'description');

        $user = User::factory()->create();
        $conversation->addParticipant($user);


        $user->exitConversation($conversation);

        #get participant 
        $participant= $conversation->participant($user);

        //assert
        expect($participant->hasExited())->toBe(true);
        expect($participant->exited_at)->not->toBe(null);

    });

 
})->only();
