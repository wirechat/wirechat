<?php

use Illuminate\Support\Facades\Event;
use Namu\WireChat\Events\MessageDeleted;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

describe(' Data verifiction ', function () {

    test('message id  is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = Message::factory()->sender($auth)->create();

        MessageDeleted::dispatch($message);
        Event::assertDispatched(MessageDeleted::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['id'])->toBe($message->id);

            return $this;
        });
    });

    test('conversation id is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = Message::factory()->sender($auth)->create();

        MessageDeleted::dispatch($message);
        Event::assertDispatched(MessageDeleted::class, function ($event) use ($message) {
            $broadcastMessage = (array) $event->broadcastWith();

            expect($broadcastMessage['message']['conversation_id'])->toBe($message->conversation_id);

            return $this;
        });
    });

    it(' broadcasts on correct  private channnel', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = Message::factory()->sender($auth)->create();

        MessageDeleted::dispatch($message);
        Event::assertDispatched(MessageDeleted::class, function ($event) use ($message) {
            $broadcastOn = $event->broadcastOn();
            expect($broadcastOn[0]->name)->toBe('private-conversation.'.$message->conversation_id);

            return $this;
        });
    });

    it(' broadcasts only on correct 1  private channnel', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = Message::factory()->sender($auth)->create();

        MessageDeleted::dispatch($message);
        Event::assertDispatched(MessageDeleted::class, function ($event) {
            $broadcastOn = $event->broadcastOn();
            expect(count($broadcastOn))->toBe(1);

            return $this;
        });
    });

});
