<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Action;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\group;
use Workbench\App\Models\User;




// it(' saves unique id when conversation is created', function () {

//     $auth = User::factory()->create();
//     $receiver = User::factory()->create();

//     //Authenticate $auth
//     $this->actingAs($auth);

//     //Create conversation
//     $conversation = $auth->sendMessageTo($receiver, message: '1')->conversation;

//     expect($conversation->unique_id)->not->toBe(null);

// });
describe('MarkAsRead()', function () {

    // it('aborts with 401 is auth is not authenticated', function () {

    //     $auth = User::factory()->create();

    //     $conversation = Conversation::factory()->withParticipants([$auth])->create();


    //     $conversation->markAsRead();


    // })->throws(Exception::class);

    it('marks messages as read', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $conversation =  $auth->sendMessageTo($receiver, message: '2')->conversation;


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

describe('AddParticipant()', function () {

    it('can add a participants to a conversation', function () {

        $auth = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->addParticipant($auth);

        expect(count($conversation->participants()->get()))->toBe(1);
    });

    it('does not add same participant to conversation -aborts 422', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);

        $conversation->addParticipant($auth);

        // dd($conversation->users);

        expect(count($conversation->participants()->get()))->toBe(1);
    })->throws(Exception::class, 'Participant is already in the conversation.');

    it('does not add more than 2 participants to a PRIVATE conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        expect($conversation->participants()->count())->toBe(2);
    })->throws(Exception::class,'Private conversations cannot have more than two participants.');

    it('does not add more than 1 participant to a SELF conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type'=>ConversationType::SELF]);

        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create())
        ->assertStatus(422,'Self conversations cannot have more than 1 participant.');

        expect($conversation->participants()->count())->toBe(1);

    })->throws(Exception::class,'Self conversations cannot have more than one participant.');

    it('can add more than 2 participants if it is a  GROUP conversation', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type' => ConversationType::GROUP]);

        // dd($conversation);
        $conversation->addParticipant($auth);
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());


        expect($conversation->participants()->count())->toBe(4);
    });

    it('aborts if user who exited conversation is added', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test group');

        #add user
        $user = User::factory()->create(['name'=>'user']);
        $conversation->addParticipant($user);

        #assert count is 2 
        expect($conversation->participants()->count())->toBe(2);

        #let user exit conversation 
        $user->exitConversation($conversation);


        #assert new count is 1 
        expect($conversation->participants()->count())->toBe(1);

        #attemp to readd user
        $conversation->addParticipant($user);


        #assert new count is still 1 
        expect($conversation->participants()->count())->toBe(1);
    })->throws(Exception::class,'Cannot add user because they left the group.');


    it('aborts if user who was removed is added and revive was false', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createGroup('test group');

        #add user
        $user = User::factory()->create(['name'=>'user']);
        $conversation->addParticipant($user);

        #assert count is 2 
        expect($conversation->participants()->count())->toBe(2);

        #remove user from group
        $userParticipant= $conversation->participant($user);
        $userParticipant->removeByAdmin($auth);

        #assert new count is 1 
        expect($conversation->participants()->count())->toBe(1);

        #attemp to readd user
        $conversation->addParticipant($user);

        #assert new count is still 1 
        expect($conversation->participants()->count())->toBe(1);
    })->throws(Exception::class,'Cannot add user because they were removed from the group by an Admin.');

});

describe('getUnreadCountFor()', function () {

    it('returns unread messages count for the specified user', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        //Authenticate $auth
        $this->actingAs($auth);

        //Create conversation
        //auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: '1')->conversation;
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


describe('deleting for', function () {


    it('load all conversations if not deleted', function () {
        $auth = User::factory()->create();

        //Authenticate
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        //send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo(User::factory()->create(), 'hello-2');
        $auth->sendMessageTo(User::factory()->create(), 'hello-3');

        //assert count

        /// dd($messages);
        expect($auth->conversations->count())->toBe(3);
    });





    it('aborts if user does not belong to conversation when deletingForMe', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        //Authenticate
        $this->actingAs($auth);

        //delete messages
        $conversation->deleteFor(User::factory()->create());

        //assert new count
        expect($conversation->count())->toBe(1);
    })->throws(Exception::class);


    it('loads deleted  conversations(for me) in query', function () {

        //Dusk to 
        $auth = User::factory()->create();

        //Send to receiver
        $conversation1 =  $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 =  $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 =  $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        //Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);


        //Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->deleteFor($auth);

        //conversations
        expect($auth->conversations()->count())->toBe(3);
    });


    it('does not loads deleted  conversations(for me) in query when ->withoutDeleted() scope is used ', function () {

        //Dusk to 
        $auth = User::factory()->create();

        //Send to receiver
        $conversation1 =  $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 =  $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        Carbon::setTestNow(now()->addSeconds(1));

        $conversation3 =  $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;
        $this->actingAs($auth);

        //Assert Count
        expect($auth->conversations()->withoutDeleted()->count())->toBe(3);


        //Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->deleteFor($auth);

        //conversations
        expect($auth->conversations()->withoutDeleted()->count())->toBe(2);
    });



    it('deletes and does not load deleted conversations(for me) if scopewithoutCleared is added', function () {

        //Dusk to 
        $auth = User::factory()->create();

        //Send to receiver
        $conversation1 =  $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 =  $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $conversation3 =  $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        //Assert Count
        expect($auth->conversations()->withoutCleared()->count())->toBe(3);

        //Authenticate
        //$auth->refresh();

        $this->actingAs($auth);

        //Delete Conversation
        $conversation3->deleteFor($auth);

        //conversations
        expect($auth->conversations()->withoutCleared()->count())->toBe(2);
    });



    it('triggers delete messages for me when conversation is deleted', function () {

        //Dusk to 

        $auth = User::factory()->create();
        $receiver = User::factory()->create();



        //Send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');
        $conversation =  $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        //Assert Count
        expect($conversation->messages()->count())->toBe(4);

        //Authenticate
        //$auth->refresh();

        $this->actingAs($auth);

        //Delete Conversation
        $conversation->deleteFor($auth);

        $this->actingAs($auth);

        expect($conversation->messages()->count())->toBe(0);
    });

    test('other user can still access the converstion if other user deletes it ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //Send to receiver
        $conversation =  $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        //Authenticate and delete 1
        $this->actingAs($auth);
        $conversation->deleteFor($auth);
        expect($auth->conversations()->withoutCleared()->count())->toBe(0);


        //Authenticate and delete 2
        $this->actingAs($receiver);
        expect($receiver->conversations()->withoutCleared()->count())->toBe(1);
    });


    test('it shows conversation again if new message is send to conversation after deleting', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //Send to receiver
        Carbon::setTestNow(now()->addSeconds(1));
        $conversation =  $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        //Authenticate and delete 1
        $this->actingAs($auth);
        Carbon::setTestNow(now()->addSeconds(2));

        $conversation->deleteFor($auth);

        //assert
        expect($auth->conversations()->withoutCleared()->count())->toBe(0);


        //send message to $auth
        Carbon::setTestNow(now()->addSeconds(3));
        $receiver->sendMessageTo($auth, 'hello-5');

        //assert again
        expect($auth->conversations()->count())->toBe(1);
    });

    it('completely deletes the conversation if both users in a private conversation has deleted conversation(All messages)', function () {


        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //Send to receiver
        $conversation =  $auth->sendMessageTo($receiver, 'hello-4')->conversation;

        //Authenticate and delete 1

        $this->actingAs($auth);
        Carbon::setTestNow(now()->addSeconds(10));
        $auth->sendMessageTo($receiver, 'hello75');

        Carbon::setTestNow(now()->addSeconds(26));

        Carbon::setTestNow(now()->addSeconds(27));
    
        $conversation->deleteFor($auth);

        Auth::logout();

        //Authenticate and delete 2

        $this->actingAs($receiver);

        Carbon::setTestNow(now()->addSeconds(28));
        $receiver->sendMessageTo($auth, 'hello-5');

        Carbon::setTestNow(now()->addSeconds(29));

        $conversation->deleteFor($receiver);

        expect(Conversation::find($conversation->id))->toBe(null);
    });


    it('completely deletes the conversation if conversation is self conversation with initiator(User)', function () {


        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        //Send to self
        $conversation =  $auth->sendMessageTo($auth, 'hello-4')->conversation;

        //Authenticate and delete 1
        $conversation->deleteFor($auth);

        expect(Conversation::withoutGlobalScopes()->where('id', $conversation->id)->first())->toBe(null);
    });



    it('it saves or set conversation_deleted_at after deleting conversation ', function () {


        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $this->actingAs($auth);
        //Send to self
        $conversation =  $auth->createConversationWith($receiver, 'helo');

        //Authenticate and delete 1
        $conversation->deleteFor($auth);


        $participant = $conversation->participant($auth);



        //  dd(Conversation::all());

        expect($participant->conversation_deleted_at)->not->toBe(null);
    });


    it('it does not exludes deleted conversation from query if not new message is available', function () {


        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $this->actingAs($auth);
        //Send to self
        Carbon::setTestNow(now());

        $conversation =  $auth->createConversationWith($receiver, 'helo');
        Carbon::setTestNow(now()->addMinute(20));

        //Authenticate and delete 1
        $conversation->deleteFor($auth);

        $auth->refresh();

        expect(count($auth->conversations()->get()))->toBe(1);
    });


    it('always adds deleted conversation to query even after sending new message', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $this->actingAs($auth);
        //!we set custom time because time is same or models during test - maube it's bug
        Carbon::setTestNow(now());
        $conversation =  $auth->createConversationWith($receiver, 'helo');

        Carbon::setTestNow(now()->addSeconds(10));

        //Authenticate and delete 1
        $conversation->deleteFor($auth);


        //assert 0 for now
        expect(count($auth->conversations()->get()))->toBe(1);

        //send me message 
        Carbon::setTestNow(now()->addSeconds(20));
        $auth->sendMessageTo($receiver, 'hello');

        $auth->refresh();
        expect(count($auth->conversations()->get()))->toBe(1);
    });
});

describe('ClearFor()', function () {

    it('loads all conversations if not cleared', function () {
        $auth = User::factory()->create();

        //Authenticate
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        //send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo(User::factory()->create(), 'hello-2');
        $auth->sendMessageTo(User::factory()->create(), 'hello-3');

        //assert count

        /// dd($messages);
        expect($auth->conversations->count())->toBe(3);
    });


    it('aborts if user does not belong to conversation when deletingForMe', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        //Authenticate
        $this->actingAs($auth);

        //delete messages
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation->clearFor(User::factory()->create());

        //assert new count
        expect($conversation->count())->toBe(1);
    })->throws(Exception::class);


    it('cleared conversation still appear in query', function () {

        //Dusk to 
        $auth = User::factory()->create();

        //Send to receiver
        $conversation1 =  $auth->sendMessageTo(User::factory()->create(), 'hello-1')->conversation;
        $conversation2 =  $auth->sendMessageTo(User::factory()->create(), 'hello-2')->conversation;
        $conversation3 =  $auth->sendMessageTo(User::factory()->create(['name' => 'john']), 'hello-3')->conversation;

        //Assert Count
        expect($auth->conversations()->count())->toBe(3);

        //Authenticate
        //$auth->refresh();

        $this->actingAs($auth);

        //Delete Conversation
        Carbon::setTestNow(now()->addSeconds(5));
        $conversation3->clearFor($auth);

        //conversations
        expect($auth->conversations()->count())->toBe(3);
    });






    test('user cannot no longer see cleared messages', function () {

        //Dusk to 

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(2));

        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(2));

        expect($conversation->messages()->count())->toBe(0);

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        //login so the messages scope will be applied
        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(4);

        //Delete Conversation
        Carbon::setTestNow(now()->addSeconds(10));
        $conversation->clearFor($auth);

        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(0);
    });



    test('Other user/users can still see cleared messages by auth', function () {

        //Dusk to 
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(2));

        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(2));
        expect($conversation->messages()->count())->toBe(0);

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        //login so the messages scope will be applied
        Carbon::setTestNow(now()->addSeconds(10));
        $this->actingAs($auth);
        expect($conversation->messages()->count())->toBe(4);

        //Delete Conversation
        Carbon::setTestNow(now()->addSeconds(20));
        $conversation->clearFor($auth);


        Auth::logout();
        //login as other user
        Carbon::setTestNow(now()->addSeconds(30));
        $this->actingAs($receiver);

        expect($conversation->messages()->count())->toBe(4);
    });
});

describe('deleting permanently()', function () {

    it('deletes all it\'s participants when conversation is deleted inluding exited and remove_by_admin', function () {

        $auth = User::factory()->create();

        $conversation = Conversation::factory()->create(['type' => ConversationType::GROUP]);

        // dd($conversation);
        $conversation->addParticipant($auth);


        $participant=   $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());
        $conversation->addParticipant(User::factory()->create());

        //assert available 
        expect($conversation->participants()->count())->toBe(4);

         
        #exit to create hidden participants
        $participant->exitConversation();

        //delete conversation 

        $conversation->delete();


        expect($conversation->participants()->withoutGlobalScopes()->count())->toBe(0);
    });


    it('deletes reads when conversation is deleted ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation =
            $receiver->sendMessageTo($auth, 'hello')->conversation;
        $auth->sendMessageTo($receiver, 'how do you do ');



        //mark as read for $auth
        $conversation->markAsRead($auth);
        $conversation->markAsRead($receiver);


        //get conversation reads
        expect($conversation->reads()->count())->toBe(2);


        //Delete message
        $conversation->delete();

        //assert count
        expect($conversation->reads()->count())->toBe(0);
    });


    it('deletes group when conversation is deleted ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();


        $conversation = $auth->createGroup('Test');
        $group = $conversation->group;

        //get conversation reads
        expect(Group::find($group->id))->not->toBe(null);


        //Delete message
        $conversation->delete();

        //assert count
        expect(Group::find($group->id))->toBe(null);
    });


    it('deletes all messages when converstion is deleted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation = Conversation::factory()->withParticipants([$receiver, $auth])->create(['type' => ConversationType::PRIVATE]);

        //dd($conversation);
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');

        //assert available 
        expect($conversation->messages()->count())->toBe(5);

        //delete conversation 
        $conversation->delete();


        expect($conversation->messages()->count())->toBe(0);
    });


    it('also deletes all messages with hidden scope when converstion is deleted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();


        $conversation = Conversation::factory()->withParticipants([$receiver, $auth])->create(['type' => ConversationType::PRIVATE]);

        /* perfomr actions that hide some message from queries */

        #delete for 
        $message=   $auth->sendMessageTo($receiver, 'hello');
        $message->deleteFor($receiver);


        #soft delete
        $message= $auth->sendMessageTo($receiver, 'hello');
        $message->delete();

        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');
        $auth->sendMessageTo($receiver, 'hello');

        $this->actingAs($receiver);

        //assert available 
        expect($conversation->messages()->count())->toBe(4);

        //delete conversation 
        $conversation->delete();



        expect($conversation->messages()->withoutGlobalScopes()->count())->toBe(0);

        expect(Message::withoutGlobalScopes()->count())->toBe(0);
    });
});
