<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\MessageDeleted;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Jobs\NotifyParticipants;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;


test('it dispatches event in job', function () {

    Bus::fake(); 
    $auth = User::factory()->create();
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->sendMessageTo($receiver,'hello')->conversation;

    $message = Message::factory()->sender($auth)->create();

    NotifyParticipants::dispatch($conversation,$message);

    Bus::assertDispatched(NotifyParticipants::class);

});

describe(" Data verifiction ", function () {

    
    test('timeout is 60 seconds', function () {

        Bus::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->sendMessageTo($receiver,'hello')->conversation;

        $message = Message::factory()->sender($auth)->create();

        NotifyParticipants::dispatch($conversation,$message);
        Bus::assertDispatched(NotifyParticipants::class,function ($event) use ($conversation, $message) {

            expect($event->timeout)->toBe(60);
    
            return $this;
        });
    
    });

    test('retry_after is 65 seconds', function () {

        Event::fake();
        Bus::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->sendMessageTo($receiver,'hello')->conversation;

        $message = Message::factory()->sender($auth)->create();

        NotifyParticipants::dispatch($conversation,$message);
        Bus::assertDispatched(NotifyParticipants::class,function ($event) use ($conversation, $message) {

            expect($event->retry_after)->toBe(65);
    
            return $this;
        });
    
    });

    


});

 