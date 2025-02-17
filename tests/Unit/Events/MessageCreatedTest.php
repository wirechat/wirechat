<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

describe('broadcastWith() Data verifiction ', function () {

    test('message id  is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = Message::factory()->sender($auth)->create();

        broadcast(new MessageCreated($message))->toOthers();
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

        $message = Message::factory()->sender($auth)->create();

        broadcast(new MessageCreated($message))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
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

        broadcast(new MessageCreated($message))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
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

        broadcast(new MessageCreated($message))->toOthers();
        Event::assertDispatched(MessageCreated::class, function ($event) {
            $broadcastOn = $event->broadcastOn();
            expect(count($broadcastOn))->toBe(1);

            return $this;
        });
    });

});

describe('Actions', function () {

    it('broadcasts to event if message is less than 1 minute old', function () {

        Event::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        // Set time to 27 seconds in the future (message is not expired)
        Carbon::setTestNow(now()->addSeconds(27));

        MessageCreated::dispatch($message);

        // Assert the event is dispatched and validate broadcastWhen logic
        Event::assertDispatched(MessageCreated::class, function ($event) {
            $broadcastOn = $event->broadcastWhen();

            // Check that broadcastWhen returned true
            expect($broadcastOn)->toBe(true); // NOT-EXPIRED

            return true; // Indicate the event was correctly validated
        });

    });

    it('does not broadcast to event if message is over 1 minute old', function () {

        Event::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        // set time to 70 seconds in the future
        Carbon::setTestNowAndTimezone(now()->addSeconds(65));

        MessageCreated::dispatch($message);

        // assert event disptaches but fails
        Event::assertDispatched(MessageCreated::class, function ($event) {
            $broadcastOn = $event->broadcastWhen();

            // Check that broadcastWhen returned true
            expect($broadcastOn)->toBe(false); // EXPIRED

            return true; // Indicate the event was correctly validated
        });
    });

});
