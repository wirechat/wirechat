<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Namu\WireChat\Events\NotifyParticipant;
use Workbench\App\Models\User;

describe(' Data verifiction ', function () {

    test('message id  is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        NotifyParticipant::dispatch($participant, $message);
        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();
            expect($broadcastMessage['message']['id'])->toBe($message->id);

            return $this;
        });
    });

    test('conversation id is present', function () {

        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        NotifyParticipant::dispatch($participant, $message);
        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($message) {

            $broadcastMessage = (array) $event->broadcastWith();

            expect($broadcastMessage['message']['conversation_id'])->toBe($message->conversation_id);

            return $this;
        });
    });

    it(' broadcasts on correct  private channnel', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        NotifyParticipant::dispatch($participant, $message);
        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($participant) {

            $broadcastOn = $event->broadcastOn();
            expect($broadcastOn[0]->name)->toBe('private-participant.'.$participant->participantable_id);

            return $this;
        });
    });

    it(' broadcasts only on correct 1  private channnel', function () {
        Event::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        NotifyParticipant::dispatch($participant, $message);
        Event::assertDispatched(NotifyParticipant::class, function ($event) {

            $broadcastOn = $event->broadcastOn();
            expect(count($broadcastOn))->toBe(1);

            return $this;
        });
    });

});

describe('Actions', function () {

    it('broadcasts to event if message is less than 2 minutes old', function () {

        Event::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        // Set future time
        Carbon::setTestNow(now()->addSeconds(59));

        NotifyParticipant::dispatch($participant, $message);

        // Assert the event is dispatched and validate broadcastWhen logic
        Event::assertDispatched(NotifyParticipant::class, function ($event) {
            $broadcastOn = $event->broadcastWhen();

            // Check that broadcastWhen returned true
            expect($broadcastOn)->toBe(true); // NOT-EXPIRED

            return true; // Indicate the event was correctly validated
        });

    });

    it('does not broadcast to event if message is over 2 minutes old', function () {

        Event::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $message = $auth->sendMessageTo($receiver, 'hello');

        $participant = $message->conversation->participant($receiver);

        //set future time
        Carbon::setTestNowAndTimezone(now()->addMinutes(3));

        NotifyParticipant::dispatch($participant, $message);

        //assert event disptaches but fails
        Event::assertDispatched(NotifyParticipant::class, function ($event) {
            $broadcastOn = $event->broadcastWhen();

            // Check that broadcastWhen returned true
            expect($broadcastOn)->toBe(false); // EXPIRED

            return true; // Indicate the event was correctly validated
        });
    });

});
