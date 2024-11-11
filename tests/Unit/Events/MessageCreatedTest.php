<?php

use Illuminate\Support\Facades\Event;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

describe("broadcastWith() Data verifiction ", function () {

    test('message id  is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $message = Message::factory()->sender($auth)->create();

        $message->load('sendable');

        broadcast(new MessageCreated($message, $conversation))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message_id'])->toBe($message->id);

            return $this;
        });
    });

    test('conversation id is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $message = Message::factory()->sender($auth)->create();

        broadcast(new MessageCreated($message, $conversation))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['conversation_id'])->toBe($message->conversation_id);
            return $this;
        });
    });

});
