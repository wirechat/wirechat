<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Livewire\Chat\ChatBox;
use Namu\WireChat\Livewire\Chat\ChatList;
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
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->withParticipants([$auth])->create();


    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(200);
});


test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => 1])
        ->assertStatus(404);
});



test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(403);
});


describe('Box presence test: ', function () {


    test('it shows receiver name when conversation is loaded in chatbox', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();
        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee("John");
    });



    test('it loads messages if they Exists in the conversation', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        //send messages
        $auth->sendMessageTo($receiver, message: 'How are you');
        $receiver->sendMessageTo($auth, message: 'i am good thanks');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('How are you')
            ->assertSee('i am good thanks');
    });
});


describe('Sending messages ', function () {

    //message
    test('it renders new message to chatbox when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", 'New message')
            ->call("sendMessage")
            ->assertSee("New message")

        ;
    });

    test('it saves new message to database when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", 'New message')
            ->call("sendMessage");

        $messageExists = Message::where('body', 'New message')->exists();

        expect($messageExists)->toBe(true);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when message is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", 'New message')
            ->call("sendMessage")
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });


    test('it pushes job "BroadcastMessage" when message is sent', function () {
        Event::fake();
        Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", 'New message')
            ->call("sendMessage");

        $message = Message::first();

        Queue::assertPushed(BroadcastMessage::class, function ($event) use ($conversation,$message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });


    test('it broadcasts event "MessageCreated" when message is sent', function () {
        Event::fake();
     //   Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", 'New message')
            ->call("sendMessage");

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message,$receiver) {
            return $event->message->id === $message->id && $event->receiver->id === $receiver->id;
        });
    });

    test('sending messages is rate limited by 50 in 60 seconds', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        for ($i = 0; $i < 60; $i++) {
            $request->set("body", 'New message')->call("sendMessage");
        }

        $request->set("body", 'New message')->call("sendMessage");

        $request->assertStatus(429);
    });


    //sending like
    test('it renders heart(❤️) to chatbox when it sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("sendLike")
            ->assertSee("❤️");
    });

    test('it saves the heart(❤️) to database when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("sendLike");

        $messageExists = Message::where('body', '❤️')->exists();
        expect($messageExists)->toBe(true);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("sendLike")
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });


    test('it pushes job "BroadcastMessage" when sendLike is called', function () {
        Event::fake();
        Queue::fake();
     
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("sendLike");

        $message = Message::first();

        Queue::assertPushed(BroadcastMessage::class, function ($event) use ($conversation,$message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it broadcasts event "MessageCreated" when sendLike is called', function () {
        Event::fake();
       // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("sendLike");

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message,$receiver) {
            return $event->message->id === $message->id && $event->receiver->id === $receiver->id;

        });
    });

    test('sending hearts(❤️) is rate limited by 50 in 60 seconds', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        for ($i = 0; $i < 60; $i++) {
            $request->call("sendLike");
        }

        $request->call("sendLike");

        $request->assertStatus(429);
    });


    //attchements


    test('it saves image to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("media", $file)
            ->call("sendMessage")
            //now assert that media is back to empty
            ->assertSet('media', []);

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    });

    test('it renders image  to chatbox when it attachement is sent & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("media", $file)
            ->call("sendMessage")
            ->assertSeeHtml("<img ")
            //now assert that media is back to empty
            ->assertSet('media', []);

        // $messageExists = Attachment::all();
        // dd($messageExists);

    });

    //video
    test('it saves video to databse when created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("media", $file)
            ->call("sendMessage");

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    })->skip();



    test('it saves file to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $file[] = UploadedFile::fake()->create('photo.pdf', '400', 'application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("files", $file)
            ->call("sendMessage")
            //now assert that file is back to empty
            ->assertSet('files', []);

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    });

    test('dispatched event is listened to in chatlist after message is created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //assert no message yet
        $chatListComponet = Livewire::actingAs($auth)->test(ChatList::class)->assertDontSee("new message");

        //send message
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set("body", "new message")
            ->call("sendMessage");

        //assert message created
        $chatListComponet->dispatch("refresh")->assertSee("new message");
    });


});

describe('Sending reply', function () {


    //reply messages 

    test('it returns abort(403) when replying if message does not belong to this conversation or is not owned by any participant', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //send message
        $auth->sendMessageTo($receiver, message: 'How are you');

        //create random message not belonging to auth user
        $randomMessage = Message::factory()->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("setReply", $randomMessage)
            ->assertStatus(403);
    });

    test('it can set reply message when setReply is called', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        //send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("setReply", $message)
            ->assertSet("replyMessage", $message);
    });

    test('it dispatches "focus-input-field" when reply is set', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        //send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("setReply", $message)
            ->assertDispatched('focus-input-field');
    });

    test('it can remove reply message when removeReply is called ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();


        //send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("removeReply")
            ->assertSet("replyMessage", null);
    });
});

describe('Deleting Conversation', function () {


    test('it redirects to wirechat route after deleting conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call("deleteConversation")
            ->assertStatus(200)
            ->assertRedirect(route("wirechat"));
    });

    test('user can no longer access deleted conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("deleteConversation");

        //assert conversation will be null
        expect($auth->conversations()->first())->toBe(null);


        //also assert that user receives 403 forbidden
        $this->get(route("wirechat.chat", $conversation->id))->assertStatus(403);
    });

    test('user can regain access to deleted conversation if receiver/other user send a new message', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("deleteConversation");


        //let receiver send a new message
        $receiver->sendMessageTo($auth, message: '5');

        //assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);


        //also assert that user receives 403 forbidden
        $this->actingAs($auth)->get(route("wirechat.chat", $conversation->id))->assertStatus(200);
    });

    test('user can regain access to deleted conversation if they send a new message after deleting conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("deleteConversation");


        //let auth send a new message to conversation after deleting
        $auth->sendMessageTo($receiver, message: '5');

        //assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);

        //also assert that user receives 403 forbidden
        $this->actingAs($auth)->get(route("wirechat.chat", $conversation->id))->assertStatus(200);
    });

    test('deleted convesation should be available in database if only one user has deleted it', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("deleteConversation");

        $conversation = Conversation::find($conversation->id);
        expect($conversation)->not->toBe(null);
    });

    test('user shold not be able to see previous messages present when conversation was deleted if they send a new message, but should be able to see new ones ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        //begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call("deleteConversation");


        //send new message in order to gain access to converstion
        $auth->sendMessageTo($receiver, message: '5 message');

        //open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        //assert user can't see previous messages
        $request2
            ->assertDontSee("1 message")
            ->assertDontSee("2 message")
            ->assertDontSee("3 message")
            ->assertDontSee("4 message");

        //assert user can see new messages
        $request2
            ->assertSee("5 message");
    });

    test('receiver in the conversation should be able to see all messages even when auth/other user deletes conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        ///reqeust for $auth to delete conversation
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("deleteConversation");

        //send after deleting conversation
        $auth->sendMessageTo($receiver, message: '5 message');

        ///request for $receiver to access conversation
        $request = Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id]);


        //assert receiver can see previous messages
        $request
            ->assertSee("1 message")
            ->assertSee("2 message")
            ->assertSee("3 message")
            ->assertSee("4 message");

        //assert user can see new messages
        $request
            ->assertSee("5 message");
    });
})->skip();

describe('Unsending Message', function () {


    test('user cannot delete message that does not belong to them ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1');
        $auth->sendMessageTo($receiver, message: 'message-2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage =  $receiver->sendMessageTo($auth, message: 'message-4');

        //run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("unSendMessage", $otherUserMessage->id)
            ->assertStatus(403);

        $messageAvailable = Message::find($otherUserMessage->id);

        ///assert message no longer visible
        expect($messageAvailable)->not->toBe(null);
    });


    test('unsent message is removed from blade', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1');
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');


        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        ///assert that message is visibible before unsending
        $request->assertSee('message-2');

        //call unsendMessage
        $request->call("unSendMessage", $authMessage->id);

        ///assert message no longer visible
        $request->assertDontSee('message-2');
    })->skip();

    test('unsent message is removed database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);


        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        //auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1');
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        //receiver -> auth 
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        //run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call("unSendMessage", $authMessage->id);

        $messageAvailable = Message::find($authMessage->id);

        ///assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('it deletes attachment from database when message is deleted ', function () {

        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $file[] = UploadedFile::fake()->image('photo.png');

        //run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            //add attachment
            ->set("media", $file)
            ->call("sendMessage");

        ///lets make sure atttachemnt is present in database

        expect(count(Attachment::all()))->toBe(1);

        //Now lets unsend message
        //here assuming that the message ID is 1 since it is the first one
        $request->call("unSendMessage", 1);


        ///assert attachment no longer avaible in database
        expect(count(Attachment::all()))->toBe(0);
    });

    test('it deletes attachment file from folder when message is deleted ', function () {

        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));


        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
                        ->withParticipants([$auth,$receiver])
            ->create();

        $file[] = UploadedFile::fake()->image('photo.png');

        //run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            //add attachment
            ->set("media", $file)
            ->call("sendMessage");

        $attachmentModel = Attachment::first();
        $messageModel = Message::first();


        //Now lets unsend message
        //here assuming that the message ID is 1 since it is the first one
        $request->call("unSendMessage", $messageModel->id);


        Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertMissing($attachmentModel->file_name);
    });
})->skip();
