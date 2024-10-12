<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;



    it('returns conversation', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->create();
        
        expect($message)->not->toBe(null);

    });



    it('returns user when sendable is called ', function () {
        $auth = User::factory()->create();
        $message  = Message::factory()->sender($auth)->create();

        //dd($message->sendable);
        expect($message->sendable->id)->toBe($auth->id);
        expect(get_class($message->sendable))->toBe(get_class($auth));


    });

     it('returns correct attachment ', function () {
        $auth = User::factory()->create();
 
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        #save photo to disk 
        $path =  $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk','public'));

  

        //create message
        $message  = Message::factory()->sender($auth)->create();

      #create attachment
      $attachment =  Attachment::factory()->for($message,'attachable')->create([
        'file_path' => $path,
        'file_name' => basename($path),
        'original_name' => $attachment->getClientOriginalName(),
        'mime_type' => $attachment->getMimeType(),
        'url' => url($path)
    ]);
        //dd($message->sendable);
        expect($message->attachment->id)->toBe($attachment->id);

    });


    it('deletes attachment from database when message is deleted', function () {
        $auth = User::factory()->create();
 
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        #save photo to disk 
        $path =  $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk','public'));

        #create attachment
       

        //create message
        $message  = Message::factory()->sender($auth)->create();
        $attachment= Attachment::factory()->for($message,'attachable')->create([

            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path)
        
        ]);
        //assert
        expect($message->attachment->id)->toBe($attachment->id);

        //delete message
        $message->delete();
 
        //assert
        expect(Attachment::find($attachment->id))->toBe(null);


    });

    it('deletes attachment from storage when message is deleted', function () {
        $auth = User::factory()->create();
 
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        #save photo to disk 
        $path =  $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk','public'));

        #create attachment
   
        //create message
        $message  = Message::factory()->sender($auth)->create();

       $attachment= Attachment::factory()->for($message,'attachable')->create([

            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path)
        
        ]);

        //assert
        expect($message->attachment->id)->toBe($attachment->id);

        //delete message
        $message->delete();
 
        //assert
        Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertMissing($attachment->file_name);



    });


    // it('returns reads count', function () {
    //     $auth = User::factory()->create();
    //     $message = Message::factory()->sender($auth)->create();

    //     for ($i=0; $i < 10; $i++) { 

    //       $user=  User::factory()->create();
    //       $message->reads()->firstOrCreate([
    //         'readable_id' => $user->id,
    //         'readable_type' => get_class($user),
    //     ], [
    //         'read_at' => now(),
    //     ]);
    //     }

    //     expect($message->reads->count())->toBe(10);

    // });


    describe('Delete Permanently',function(){


    it('deletes actions when message is deleted ', function () {
        
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        //send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $message1=  $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');

        //authenticate
        $this->actingAs($auth);

        //send to auth
        $receiver->sendMessageTo($auth,'hello-4');
        $receiver->sendMessageTo($auth,'hello-5');
        $receiver->sendMessageTo($auth,'hello-6');

        //assert count is 6
        expect($conversation->messages()->count())->toBe(6);


        //delete messages
        $message1->deleteFor($auth);


        //assert actions

        expect($message1->actions()->count())->toBe(1);


        //Permantly Delete message
        $message1->delete();

        //assert count
        expect($message1->actions()->count())->toBe(0);


    });
});


   describe('DeleteForMe',function(){


    it('load all messages if not deleted', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        //send to receiver

        $auth->sendMessageTo($receiver,'hello-1');
        $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');


        //send to auth
        $receiver->sendMessageTo($auth,'hello-4');
        $receiver->sendMessageTo($auth,'hello-5');
        $message=  $receiver->sendMessageTo($auth,'hello-6');

        //assert count
        $messages= Message::where('conversation_id',$message->conversation_id)->get();

       /// dd($messages);
        expect($messages->count())->toBe(6);

    });


    it('aborts if user is not authenticated before deletingForMe', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        //send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $message1=  $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');
 
        //send to auth
        $receiver->sendMessageTo($auth,'hello-4');
        $receiver->sendMessageTo($auth,'hello-5');

        //delete messages
          $message1->deleteForMe();


         //assert new count
         expect($conversation->messages()->count())->toBe(6);

    })->throws(Exception::class);


    it('aborts if user does not belong to conversation  before deletingForMe', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        //send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $message1=  $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');
 
        //send to auth
        $receiver->sendMessageTo($auth,'hello-4');
        $receiver->sendMessageTo($auth,'hello-5');

        //authenticate random user
        $randomUser=  User::factory()->create();;
        $this->actingAs($randomUser);
        
        //delete messages
        $message1->deleteForMe();


         //assert new count
         expect($conversation->messages()->count())->toBe(6);

    })->throws(Exception::class);

    
    it('deletes and does not load deleted messages(for $auth)', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        //send to receiver
        $auth->sendMessageTo($receiver,'hello-1');
        $message1=  $auth->sendMessageTo($receiver,'hello-2');
        $auth->sendMessageTo($receiver,'hello-3');

        //authenticate
        $this->actingAs($auth);

        //send to auth
        $receiver->sendMessageTo($auth,'hello-4');
        $receiver->sendMessageTo($auth,'hello-5');
        $message2= $receiver->sendMessageTo($auth,'hello-6');

        //assert count is 6
        expect($conversation->messages()->count())->toBe(6);


        //delete messages
        $message1->deleteFor($auth);
        $message2->deleteFor($auth);

         //assert new count
         expect($conversation->messages()->count())->toBe(4);

    });


    
    });

 