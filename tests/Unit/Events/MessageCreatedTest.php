<?php

use Illuminate\Support\Facades\Event;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Workbench\App\Models\User;

describe("broadcastWith() Data verifiction ", function () {


    test('message data is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();

            // dd($broadcastMessage);
            expect(array_key_exists('message', $broadcastMessage))->toBeTrue();

            //assert data
            expect($broadcastMessage['message']['id'])->toBe($message->id);
            return $this;
        });
    });

    test('message id is present', function () {

        Event::fake();
$auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['id'])->toBe($message->id);

            return $this;
        });
    });

    test('conversation id is present', function () {

        Event::fake();
$auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['conversation_id'])->toBe($message->conversation_id);
            return $this;
        });
    });

    test('sender id is present', function () {

        Event::fake();
$auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['sender_id'])->toBe($message->sender_id);
            return $this;
        });
    });

    test('receiver_id is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['receiver_id'])->toBe($message->receiver_id);
            return $this;
        });
    });

    test('body id is present', function () {

        Event::fake();
$auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['body'])->toBe($message->body);
            return $this;
        });
    });

    test('attachment_id is present', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['attachment_id'])->toBe($message->attachment_id);
            return $this;
        });
    });
    
    test('reply_id is present', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['reply_id'])->toBe($message->reply_id);
            return $this;
        });
    });

    test('read_at is present', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth,$receiver])->create();

        $message = Message::factory()->create();

        broadcast(new MessageCreated($message ,$receiver))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['read_at'])->toBe($message->read_at);
            return $this;
        });
    });
});
