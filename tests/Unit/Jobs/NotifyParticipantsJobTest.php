<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Wirechat\Wirechat\Events\NotifyParticipant;
use Wirechat\Wirechat\Jobs\NotifyParticipants;
use Wirechat\Wirechat\Models\Message;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

describe(' Data verifiction ', function () {

    test('timeout is 60 seconds', function () {

        Bus::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->sendMessageTo($receiver, 'hello')->conversation;

        $message = Message::factory()->sender($auth)->create();

        NotifyParticipants::dispatch($conversation, $message);
        Bus::assertDispatched(NotifyParticipants::class, function ($event) {

            expect($event->timeout)->toBe(60);

            return $this;
        });

    });

    test('retry_after is 65 seconds', function () {

        Event::fake();
        Bus::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->sendMessageTo($receiver, 'hello')->conversation;

        $message = Message::factory()->sender($auth)->create();

        NotifyParticipants::dispatch($conversation, $message);
        Bus::assertDispatched(NotifyParticipants::class, function ($event) {

            expect($event->retry_after)->toBe(65);

            return $this;
        });

    });

});

describe('Actions', function () {

    test('it notifies participants if  message is NOT  older than 60 Seconds ', function () {

        Bus::fake();
        Event::fake();
        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job in database
        $job = (new NotifyParticipants($conversation, $message));

        // Travel future JUst 5 seconds
        $this->travelTo(now()->addSeconds(5)); // VALID

        $job->handle();

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);

    });

    test('it does not notify participants if and deltes job if message is older than 60 Seconds ', function () {

        // Bus::fake();
        Event::fake();
        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        for ($i = 0; $i <= 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        Carbon::setTestNowAndTimezone(now()->subSeconds(200));
        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job instance
        $job = (new NotifyParticipants($conversation, $message));

        // Travel future
        Carbon::setTestNowAndTimezone(now()->subSeconds(139));

        $job->handle();

        Event::assertDispatchedTimes(NotifyParticipant::class, 0);

    });

    test('it dispatches NotifyParticipant to the right number of participnats  except the Auth', function () {

        // Bus::fake();
        Bus::fake();
        Event::fake();
        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job in database
        $job = (new NotifyParticipants($conversation, $message));

        $job->handle();

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);

        Event::assertNotDispatched(NotifyParticipant::class, function ($event) use ($auth) {

            return $event->participant->participantable_id == $auth->id && $event->participant->participantable_type == $auth->getMorphClass();
        });

    });

    test('it dispatches NotifyParticipant to the right number of MIXED MODEL participnats  except the Auth', function () {

        // Bus::fake();
        Bus::fake();
        Event::fake();
        $auth = User::factory()->create();

        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        for ($i = 0; $i < 10; $i++) {
            $conversation->addParticipant(User::factory()->create());
            $conversation->addParticipant(Admin::factory()->create());

        }

        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job in database
        $job = (new NotifyParticipants($conversation, $message));

        $job->handle();

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);

        Event::assertNotDispatched(NotifyParticipant::class, function ($event) use ($auth) {

            return $event->participant->participantable_id == $auth->id && $event->participant->participantable_type == $auth->getMorphClass();
        });

    });

    test('it does not broadcasts event "NotifyParticipant" to member who exited the group-meaning expect only 20 event not 21', function () {
        Event::fake();
        //  Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add members

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);

        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        //   $user->sendMessageTo($conversation, 'hi');
        $user->exitConversation($conversation); // exit here

        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job in database
        $job = (new NotifyParticipants($conversation, $message));

        $job->handle();

        //    Carbon::setTestNow(now()->addSeconds(6));

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);
    });

    test('it broadcasts event "NotifyParticipant" to all members of group-except owner of message when message is sent', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add members
        for ($i = 0; $i < 20; $i++) {
            $conversation->addParticipant(User::factory()->create());
        }

        $message = $auth->sendMessageTo($conversation, 'hello');

        // Create Job in database
        $job = (new NotifyParticipants($conversation, $message));

        $job->handle();

        Event::assertDispatchedTimes(NotifyParticipant::class, 20);
    });

});
