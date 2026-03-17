<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

it('returns conversation', function () {
    $auth = User::factory()->create();
    $message = Message::factory()->create();

    expect($message)->not->toBe(null);
});

it('returns user when sendable is called ', function () {
    $auth = User::factory()->create();
    $message = Message::factory()->sender($auth)->create();

    // dd($message->sendable);
    expect($message->sendable->id)->toBe($auth->id);
    expect(get_class($message->sendable))->toBe(get_class($auth));
});

it('returns correct attachment ', function () {
    $auth = User::factory()->create();

    Storage::fake(config('wirechat.storage.disk', 'public'));
    $attachment = UploadedFile::fake()->image('file.png');

    // save photo to disk
    $path = $attachment->store(config('wirechat.storage.directory', 'attachments'), config('wirechat.storage.disk', 'public'));

    // create message
    $message = Message::factory()->sender($auth)->create();

    // create attachment
    $attachment = Attachment::factory()->for($message, 'attachable')->create([
        'file_path' => $path,
        'file_name' => basename($path),
        'original_name' => $attachment->getClientOriginalName(),
        'mime_type' => $attachment->getMimeType(),
        'url' => url($path),
    ]);
    // dd($message->sendable);
    expect($message->attachment->id)->toBe($attachment->id);
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

describe('DeleteFor Everyone', function () {

    it('deletes actions when message is deleted ', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $message1 = $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // authenticate
        $this->actingAs($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $receiver->sendMessageTo($auth, 'hello-6');

        // assert count is 6
        expect($conversation->messages()->count())->toBe(6);

        // delete messages
        $message1->deleteFor($auth);

        // assert actions

        expect($message1->actions()->count())->toBe(1);

        // Permantly Delete message
        $message1->delete();

        // assert count
        expect($message1->actions()->count())->toBe(0);
    });

    it('deletes attachment from database when message is deleted', function () {
        $auth = User::factory()->create();

        Storage::fake(config('wirechat.storage.disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        // save photo to disk
        $path = $attachment->store(config('wirechat.storage.directory', 'attachments'), config('wirechat.storage.disk', 'public'));

        // create attachment

        // create message
        $message = Message::factory()->sender($auth)->create();
        $attachment = Attachment::factory()->for($message, 'attachable')->create([

            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path),

        ]);
        // assert
        expect($message->attachment->id)->toBe($attachment->id);

        // delete message
        $message->delete();

        // assert
        expect(Attachment::find($attachment->id))->toBe(null);
    });

    it('deletes attachment from storage when message is deleted', function () {
        $auth = User::factory()->create();

        Storage::fake(config('wirechat.storage.disk', 'public'));
        $attachment = UploadedFile::fake()->image('file.png');

        // save photo to disk
        $path = $attachment->store(config('wirechat.storage.directory', 'attachments'), config('wirechat.storage.disk', 'public'));

        $this->actingAs($auth);
        // create attachment

        // create message
        $message = Message::factory()->sender($auth)->create();

        $attachment = Attachment::factory()->for($message, 'attachable')->create([

            'file_path' => $path,
            'file_name' => basename($path),
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getMimeType(),
            'url' => url($path),

        ]);

        // Assert the file was stored...
        Storage::disk(config('wirechat.storage.disk', 'public'))->assertExists($attachment->file_path);

        // assert
        expect($message->attachment->id)->toBe($attachment->id);

        expect(Message::where('conversation_id', $message->conversation_id)->withoutGlobalScopes()->count())->toBe(1);
        expect($message->attachment()->count())->toBe(1);
        // delete message
        $message->forceDelete();

        expect(Message::where('conversation_id', $message->conversation_id)->withoutGlobalScopes()->count())->toBe(0);
        expect($message->attachment()->count())->toBe(0);
        // assert
        Storage::disk(config('wirechat.storage.disk', 'public'))->assertMissing($attachment->file_path);
    });

    it('aborts 403 AND does not delete message if user does not belong to conversation  before deletingForEveryone', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // send to receiver
        $message1 = $auth->sendMessageTo($conversation, 'hello-1');

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        $message1->deleteForEveryone(User::factory()->create());

        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);
    })->throws(Exception::class);

    it('aborts 403 AND does not delete message if user does not own message  before deletingForEveryone', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $message1 = $auth->sendMessageTo($conversation, 'hello-1');

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        $message1->deleteForEveryone($receiver);

        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);
    })->throws(Exception::class, 'You do not have permission to delete this message');

    it('deletes if User owns messag when deletingForEveryone', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $message1 = $auth->sendMessageTo($conversation, 'hello-1');

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        $message1->deleteForEveryone($auth);

        $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $message1->id]);
    });

    it('still deletes if User does not own message but is Admin and Conversation is Group', function () {

        $auth = User::factory()->create();

        $receiver = User::factory()->create();
        $randomUser = User::factory()->create();

        // create group
        $conversation = $receiver->createGroup('Text');

        // add participants
        $conversation->addParticipant($auth, ParticipantRole::ADMIN);
        $conversation->addParticipant($randomUser);

        // send message by random user
        $message1 = $randomUser->sendMessageTo($conversation, 'hello-1');

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        // Atempf for admin to delete
        $message1->deleteForEveryone($auth);

        $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $message1->id]);
    });
});

describe('DeleteForMe', function () {

    it('load all messages if not deleted', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // send to receiver

        $auth->sendMessageTo($receiver, 'hello-1');
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();

        // / dd($messages);
        expect($messages->count())->toBe(6);
    });

    it('aborts if user is not authenticated before deletingForMe', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $message1 = $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');

        // delete messages
        $message1->deleteForMe();

        // assert new count
        expect($conversation->messages()->count())->toBe(6);
    })->throws(Exception::class);

    it('aborts if user does not belong to conversation  before deletingForMe', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $message1 = $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');

        // authenticate random user
        $randomUser = User::factory()->create();
        $this->actingAs($randomUser);

        // delete messages
        $message1->deleteForMe();

        // assert new count
        expect($conversation->messages()->count())->toBe(6);
    })->throws(Exception::class);

    it('deletes and does not load deleted messages(for $auth)', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $auth->sendMessageTo($receiver, 'hello-1');
        $message1 = $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // authenticate
        $this->actingAs($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message2 = $receiver->sendMessageTo($auth, 'hello-6');

        // assert count is 6
        expect($conversation->messages()->count())->toBe(6);

        // delete messages
        $message1->deleteFor($auth);
        $message2->deleteFor($auth);

        // assert new count
        expect($conversation->messages()->count())->toBe(4);
    });

    it('Does Not delete message from database when deleted for me', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // authenticate
        $this->actingAs($auth);
        // send to receiver
        $message1 = $auth->sendMessageTo($receiver, 'hello-1');

        // assert the message count for auth user is 1 Before Delete
        expect($conversation->messages()->count())->toBe(1);

        // delete message
        $message1->deleteFor($auth);

        // assert the message count for auth user is 0 After Delete
        expect($conversation->messages()->count())->toBe(0);

        // assert message exists in database
        $this->assertDatabaseCount((new Message)->getTable(), $message1->id);
        expect($conversation->messages()->withoutGlobalScopes()->count())->toBe(1);
    });

    test('Other Logged in users of Same Model  can still see messages even if Auth deletes For me', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $message1 = $auth->sendMessageTo($receiver, 'hello-1');

        // delete message
        $message1->deleteFor($auth);

        // authenticate as auth
        $this->actingAs($auth);

        // assert auth can see message
        expect($conversation->messages()->count())->toBe(0);

        // authenticate as receiver
        $this->actingAs($receiver);

        // assert receiever can see messages
        expect($conversation->messages()->count())->toBe(1);
    });

    test('Other Logged in users of Different Model can still see messages even if Auth deletes For me', function () {
        $auth = User::factory()->create();

        $receiver = Admin::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $message1 = $auth->sendMessageTo($receiver, 'hello-1');

        // delete message
        $message1->deleteFor($auth);

        // dd($message1,\Wirechat\Wirechat\Models\Action::first());
        // authenticate as auth
        $this->actingAs($auth);

        // assert auth can see message
        expect($conversation->messages()->count())->toBe(0);

        // authenticate as receiver
        $this->actingAs($receiver);

        // assert receiever can see messages
        expect($conversation->messages()->count())->toBe(1);
    });

    test('Message is permanently deleted when both users in a private conversation delete IT', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        // send to receiver
        $message1 = $auth->sendMessageTo($receiver, 'hello-1');

        // authenticate as auth
        $this->actingAs($auth);
        $message1->deleteFor($auth);

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        // authenticate as receiver
        $this->actingAs($receiver);
        $message1->deleteFor($receiver);

        $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $message1->id]);
    });

    it('It deletes message permanetly if conversation is Self', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        // send to receiver
        $message1 = $auth->sendMessageTo($conversation, 'hello-1');

        // assert exists in database
        $this->assertDatabaseHas((new Message)->getTable(), ['id' => $message1->id]);

        $message1->deleteFor($auth);

        $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $message1->id]);
    });
});

describe('excludeDeletedScope', function () {

    it('it excludes messages created before participant\s conversation_cleared_at is filled', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($auth);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);

        // RESET TIME
        Carbon::setTestNow();

        // set deleted at for participant
        $participant = $conversation->participant($auth);
        $participant->update(['conversation_cleared_at' => now()]);

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(0);
    });

    it('Other users can still accesss messages despite other user participant\s conversation_cleared_at is filled', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($receiver);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);

        // RESET TIME
        Carbon::setTestNow();

        // set deleted at for participant
        $participant = $conversation->participant($auth);
        $participant->update(['conversation_cleared_at' => now()]);

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);
    });

    it('Other users Of Mixed Participant can still accesss messages despite other user participant\s conversation_cleared_at is filled', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = Admin::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($receiver);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);

        // RESET TIME
        Carbon::setTestNow();

        // set deleted at for participant
        $participant = $conversation->participant($auth);
        $participant->update(['conversation_cleared_at' => now()]);

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);
    });

    it('it excludes messages created before participant\s conversation_deleted_at is filled', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');
        // Authenticate
        $this->actingAs($auth);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);

        // RESET TIME
        Carbon::setTestNow();

        // set deleted at for participant
        $participant = $conversation->participant($auth);
        $participant->update(['conversation_deleted_at' => now()]);

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(0);
    });

    it('Other users can still accesss messages despite other user  participant\'s conversation_deleted_at is filled', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3');

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4');
        $receiver->sendMessageTo($auth, 'hello-5');
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($receiver);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);

        // RESET TIME
        Carbon::setTestNow();

        // set deleted at for participant
        $participant = $conversation->participant($auth);
        $participant->update(['conversation_deleted_at' => now()]);

        // assert count
        $messages = Message::where('conversation_id', $message->conversation_id)->get();
        expect($messages->count())->toBe(6);
    });

    // With Deleted Action

    it('it excludes individual messages with deleted action', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3')->deleteFor($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4')->deleteFor($auth);
        $receiver->sendMessageTo($auth, 'hello-5')->deleteFor($auth);
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($auth);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();

        expect($messages->count())->toBe(3);

    });

    it('Other users can still accesss messages despite other user  participant\'s deleteing individual messages with  deleted action', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = User::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3')->deleteFor($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4')->deleteFor($auth);
        $receiver->sendMessageTo($auth, 'hello-5')->deleteFor($auth);
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($receiver);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();

        expect($messages->count())->toBe(6);

    });

    it('it excludes individual messages with deleted action in a coversation with Mixed Participants', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = Admin::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3')->deleteFor($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4')->deleteFor($auth);
        $receiver->sendMessageTo($auth, 'hello-5')->deleteFor($auth);
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($auth);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();

        expect($messages->count())->toBe(3);

    });

    it('Other users of Mixed Participant Model can still accesss messages despite other user  participant\'s deleteing individual messages with  deleted action', function () {
        $auth = User::factory()->create();
        $this->actingAs($auth);

        $receiver = Admin::factory()->create();

        // SET TIME
        Carbon::setTestNow(now()->subMinutes(10));

        // send to receiver

        $conversation = $auth->sendMessageTo($receiver, 'hello-1')->conversation;
        $auth->sendMessageTo($receiver, 'hello-2');
        $auth->sendMessageTo($receiver, 'hello-3')->deleteFor($auth);

        // send to auth
        $receiver->sendMessageTo($auth, 'hello-4')->deleteFor($auth);
        $receiver->sendMessageTo($auth, 'hello-5')->deleteFor($auth);
        $message = $receiver->sendMessageTo($auth, 'hello-6');

        // Authenticate
        $this->actingAs($receiver);

        $messages = Message::where('conversation_id', $message->conversation_id)->get();

        expect($messages->count())->toBe(6);

    });

});

describe('sanitized accessors', function () {

    it('returns sanitized body with unsafe HTML tags stripped but safe tags preserved', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => '<p>Hello <strong>World</strong></p>',
        ]);

        // Safe tags like <p> and <strong> should be preserved
        expect($message->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($message->sanitized_body->toHtml())->toBe('<p>Hello <strong>World</strong></p>');
    });

    it('handles null body for sanitized_body accessor', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => null,
        ]);

        expect($message->sanitized_body)->toBeNull();
    });

    it('handles null body for text_body accessor', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => null,
        ]);

        expect($message->text_body)->toBeNull();
    });

    it('handles empty string body for sanitized_body accessor', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => '',
        ]);

        expect($message->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($message->sanitized_body->toHtml())->toBe('');
    });

    it('handles empty string body for text_body accessor', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => '',
        ]);

        expect($message->text_body)->toBe('');
    });

    it('returns HtmlString when accessor is overridden to return HtmlString', function () {
        // Create a custom message class that overrides the accessor
        $customMessage = new class extends Message
        {
            public function getSanitizedBodyAttribute(): \Illuminate\Support\HtmlString|string|null
            {
                return new \Illuminate\Support\HtmlString('<p>Sanitized HTML</p>');
            }
        };

        $customMessage->body = 'Original Body';

        expect($customMessage->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($customMessage->sanitized_body->toHtml())->toBe('<p>Sanitized HTML</p>');
    });

    it('returns string when text_body accessor is overridden', function () {
        // Create a custom message class that overrides the accessor
        $customMessage = new class extends Message
        {
            public function getTextBodyAttribute(): ?string
            {
                return 'Short text';
            }
        };

        $customMessage->body = 'Original Body';

        expect($customMessage->text_body)->toBe('Short text');
    });

    it('strips unsafe HTML tags but allows safe tags in sanitized_body', function () {
        $auth = User::factory()->create();
        $bodyContent = 'This is a regular message: <script>alert("xss")</script><p>Hello <strong>World</strong></p>';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyContent,
        ]);

        // XSS script tags should be stripped, but safe tags should remain, returned as HtmlString
        expect($message->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($message->sanitized_body->toHtml())->toBe('This is a regular message: alert("xss")<p>Hello <strong>World</strong></p>');
        expect($message->sanitized_body->toHtml())->not->toContain('<script>');
        expect($message->sanitized_body->toHtml())->not->toContain('</script>');
        expect($message->sanitized_body->toHtml())->toContain('<p>');
        expect($message->sanitized_body->toHtml())->toContain('<strong>');
    });

    it('allows safe HTML tags in sanitized_body', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => '<div><p>Hello</p><script>alert("hack")</script><img src="x" onerror="alert(1)"></div>',
        ]);

        // Safe tags should remain, unsafe tags should be stripped, returned as HtmlString
        expect($message->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($message->sanitized_body->toHtml())->toBe('<div><p>Hello</p>alert("hack")</div>');
        expect($message->sanitized_body->toHtml())->not->toContain('<script>');
        expect($message->sanitized_body->toHtml())->not->toContain('<img');
        expect($message->sanitized_body->toHtml())->toContain('<div>');
        expect($message->sanitized_body->toHtml())->toContain('</div>');
        expect($message->sanitized_body->toHtml())->toContain('<p>');
    });

    it('strips HTML tags from text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyContent = '<p>Short</p> <strong>version</strong> of the message';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyContent,
        ]);

        // HTML tags should be stripped
        expect($message->text_body)->toBe('Short version of the message');
    });

    it('protects against XSS in text_body accessor', function () {
        $auth = User::factory()->create();
        $xssBody = '<script>alert("xss")</script>Hello World<script>stealCookies()</script>';
        $message = Message::factory()->sender($auth)->create([
            'body' => $xssBody,
        ]);

        // XSS scripts should be stripped
        expect($message->text_body)->toBe('alert("xss")Hello WorldstealCookies()');
        expect($message->text_body)->not->toContain('<script>');
        expect($message->text_body)->not->toContain('</script>');
    });

    it('squishes multiple spaces into single space for text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyWithExtraSpaces = 'Hello    World   with    many   spaces';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyWithExtraSpaces,
        ]);

        expect($message->text_body)->toBe('Hello World with many spaces');
    });

    it('squishes tabs and newlines into single space for text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyWithWhitespace = "Hello\t\t\tWorld\n\n\nwith\r\nmixed\twhitespace";
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyWithWhitespace,
        ]);

        expect($message->text_body)->toBe('Hello World with mixed whitespace');
    });

    it('trims leading and trailing whitespace for text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyWithLeadingTrailing = '   Hello World   ';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyWithLeadingTrailing,
        ]);

        expect($message->text_body)->toBe('Hello World');
    });

    it('handles combination of HTML stripping and squishing for text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyContent = '<p>  Hello   </p>  <strong>World</strong>   with   <em>spaces</em>  ';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyContent,
        ]);

        expect($message->text_body)->toBe('Hello World with spaces');
    });

    it('squishes whitespace without truncating for text_body accessor', function () {
        $auth = User::factory()->create();
        $bodyWithManySpaces = 'Word     AnotherWord';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyWithManySpaces,
        ]);

        expect($message->text_body)->toBe('Word AnotherWord');
        expect(strlen($message->text_body))->toBe(16);
    });

    it('squishes lots of whitespace without truncating for text_body accessor', function () {
        $auth = User::factory()->create();
        // Create a string with lots of spaces that get squished
        $bodyWithSpaces = 'Word'.str_repeat(' ', 50).'AnotherWord';
        $message = Message::factory()->sender($auth)->create([
            'body' => $bodyWithSpaces,
        ]);

        // After squishing, this should be "Word AnotherWord" (16 chars: 4 + 1 + 11)
        expect($message->text_body)->toBe('Word AnotherWord');
        expect(strlen($message->text_body))->toBe(16);
    });

    it('removes dangerous attributes from sanitized_body', function () {
        $auth = User::factory()->create();
        $message = Message::factory()->sender($auth)->create([
            'body' => '<a href="javascript:alert(1)" onclick="alert(2)">Link</a><p style="color:red" onload="alert(3)">Text</p>',
        ]);

        // Dangerous attributes should be removed, returned as HtmlString
        expect($message->sanitized_body)->toBeInstanceOf(\Illuminate\Support\HtmlString::class);
        expect($message->sanitized_body->toHtml())->not->toContain('javascript:');
        expect($message->sanitized_body->toHtml())->not->toContain('onclick');
        expect($message->sanitized_body->toHtml())->not->toContain('onload');
        expect($message->sanitized_body->toHtml())->not->toContain('style=');
        expect($message->sanitized_body->toHtml())->toContain('<a href="#"');
        expect($message->sanitized_body->toHtml())->toContain('<p>');
    });

});
