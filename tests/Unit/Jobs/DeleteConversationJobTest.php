<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Jobs\DeleteConversationJob;
use Wirechat\Wirechat\Jobs\DeleteExpiredMessagesJob;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Message;
use Workbench\App\Models\User;

test('it deletes conversation succesfully', function () {

    // Set up a conversation with disappearing messages
    $auth = User::factory()->create();

    // Set test time for 3 days ago
    Carbon::setTestNowAndTimezone(now());

    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create();

    $this->assertDatabaseHas((new Conversation)->getTable(), ['id' => $conversation->id]);

    // Run the job to delete expired messages
    DeleteConversationJob::dispatch($conversation, 'test');
    // $job = new DeleteExpiredMessagesJob;
    // $job->handle();

    // Assert that the old message is deleted
    $this->assertDatabaseMissing((new Conversation)->getTable(), ['id' => $conversation->id]);
});

test('delay is 60 seconds', function () {

    Bus::fake();
    Carbon::setTestNowAndTimezone(now());

    $auth = User::factory()->create();
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->sendMessageTo($receiver, 'hello')->conversation;

    DeleteConversationJob::dispatch($conversation);

    Bus::assertDispatched(DeleteConversationJob::class, function ($event) {
        expect((int) now()->diffInSeconds($event->delay))->toBe(5);

        return $this;
    });

});
