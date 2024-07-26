<?php

use Illuminate\Http\UploadedFile;
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


describe('Box presence test: ', function () {



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


describe('Sending messages ', function () {

    //message
    test('it renders new message to chatbox when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("body",'New message')
            ->call("sendMessage")
            ->assertSee("New message")

            ;
    });

    test('it saves new message to database when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("body",'New message')
            ->call("sendMessage");
        
        $messageExists = Message::where('body','New message')->exists();

        expect($messageExists)->toBe(true);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when message is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("body",'New message')
            ->call("sendMessage")
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });


    //heart
    test('it renders heart(❤️) to chatbox when it sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->call("sendLike")
            ->assertSee("❤️");
    });

    test('it saves the heart(❤️) to database when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);

        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
        ->call("sendLike");
        
        $messageExists = Message::where('body','❤️')->exists();
        expect($messageExists)->toBe(true);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);


        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->call("sendLike")
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });

    //attchements


    test('it saves image to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("media",$file)
            ->call("sendMessage")
              //now assert that media is back to empty
              ->assertSet('media',[]);

          $messageExists = Attachment::all();
          expect(count($messageExists))->toBe(1);


    });

    test('it renders image  to chatbox when it attachement is sent & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("media",$file)
            ->call("sendMessage")
            ->assertSeeHtml("<img ")
            //now assert that media is back to empty
            ->assertSet('media',[]);

         // $messageExists = Attachment::all();
         // dd($messageExists);

    });

    //video
    test('it saves video to databse when created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);

        $file = UploadedFile::fake()->create('sample.mp4', '1000','video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("media",$file)
            ->call("sendMessage");

          $messageExists = Attachment::all();
          expect(count($messageExists))->toBe(1);


    })->skip();



    test('it saves file to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name'=>'John']);
        $conversation = Conversation::factory()->create(['sender_id'=>$auth->id,'receiver_id'=>$receiver->id]);

        $file[] = UploadedFile::fake()->create('photo.pdf','400','application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class,['conversation' => $conversation->id])
            ->set("files",$file)
            ->call("sendMessage")
            //now assert that file is back to empty
            ->assertSet('files',[]) ;

          $messageExists = Attachment::all();
          expect(count($messageExists))->toBe(1);

    });





   


   



})->only();
