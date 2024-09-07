<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;



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

        #create attachment
        $attachment = Attachment::factory()->create([
            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path)
        ]);

        //create message
        $message  = Message::factory()->sender($auth)->create(['attachment_id'=>$attachment->id]);

        //dd($message->sendable);
        expect($message->attachment_id)->toBe($attachment->id);

    });


    it('deletes attachment from database when message is deleted', function () {
        $auth = User::factory()->create();
 
        Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        #save photo to disk 
        $path =  $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk','public'));

        #create attachment
        $attachment = Attachment::factory()->create([
            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path)
        ]);

        //create message
        $message  = Message::factory()->sender($auth)->create(['attachment_id'=>$attachment->id]);

        //assert
        expect($message->attachment_id)->toBe($attachment->id);

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
        $attachment = Attachment::factory()->create([
            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path)
        ]);

        //create message
        $message  = Message::factory()->sender($auth)->create(['attachment_id'=>$attachment->id]);

        //assert
        expect($message->attachment_id)->toBe($attachment->id);

        //delete message
        $message->delete();
 
        //assert
        Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertMissing($attachment->file_name);



    });


    it('returns reads count', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create();

        for ($i=0; $i < 10; $i++) { 

          $user=  User::factory()->create();
          $message->reads()->firstOrCreate([
            'readable_id' => $user->id,
            'readable_type' => get_class($user),
        ], [
            'read_at' => now(),
        ]);
        }

        expect($message->reads->count())->toBe(10);

    });

    it('deletes reads when message is deleted ', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create();

        //Create reads 
        for ($i=0; $i < 10; $i++) { 

          $user=  User::factory()->create();
          $message->reads()->firstOrCreate([
            'readable_id' => $user->id,
            'readable_type' => get_class($user),
        ], [
            'read_at' => now(),
        ]);
        }

        //verify count before delete 
        expect($message->reads()->count())->toBe(10);


        //Delete message
        $message->delete();

        //assert count
        expect($message->reads()->count())->toBe(0);

    });


 