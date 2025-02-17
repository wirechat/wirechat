<?php

use Carbon\Carbon;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Jobs\DeleteExpiredMessagesJob;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

test('expired_messages_are_deleted', function () {

    // **Use subHours()  because  future dates causes negative diffInSeconds()
    // Set up a conversation with disappearing messages
    $auth = User::factory()->create();

    // Set test time for 3 days ago
    Carbon::setTestNowAndTimezone(now());

    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds (duration of disappearing messages)
        'disappearing_started_at' => now()->subHours(26), // Started 3 days ago
    ]);

    // Create an old message that is outside the expiration period
    $oldMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHours(13), // Created 25 hours ago, expired
        'kept_at' => null, // Not kept
    ]);

    // Calculate the cutoff time for message expiration (disappearing_started_at + disappearing_duration)
    $cutoffTime = $conversation->disappearing_started_at->addSeconds($conversation->disappearing_duration);

    //  dd($oldMessage->created_at->diffInSeconds());
    // Assert the cutoff time is as expected (to help debug)
    // dd(['Duration expired'=>$oldMessage->created_at->diffInSeconds() > $conversation->disappearing_duration]);

    // Run the job to delete expired messages
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert that the old message is deleted
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $oldMessage->id]);
});

test('it doesnt delete messages not expired', function () {
    // **Use subHours()  because  future dates causes negative diffInSeconds()
    // Set up a conversation with disappearing messages
    $auth = User::factory()->create();

    Carbon::setTestNowAndTimezone(now());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 24 hours in seconds
        'disappearing_started_at' => Carbon::now()->subHours(5), // Started 2 days ago
    ]);

    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now()->subHours(2), // 12 hours ago, not expired
        'kept_at' => null, // Not kept
    ]);
    // dd($conversation->get(['disappearing_duration','disappearing_started_at']), $recentMessage->get(['kept_at','created_at']));

    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the recent message is still there
    $this->assertDatabaseHas((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('it doesnt delete messages created before the "disappearing_started_at"', function () {
    // Set up a conversation with disappearing messages
    $auth = User::factory()->create();

    Carbon::setTestNow(now()->today());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 86400, // 24 hours in seconds
        'disappearing_started_at' => Carbon::now()->today(), // Started 2 days ago
    ]);

    // message created long time ago
    Carbon::setTestNow(now()->subDays(7));
    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now()->subDays(7), // 12 hours ago, not expired
        'kept_at' => null, // Not kept
    ]);

    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the recent message is still there
    $this->assertDatabaseHas((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('it also deletes SoftDeleted messages that are expired and are kept ', function () {
    // Set up a conversation with disappearing messages

    // **Use subHours()  because  future dates causes negative diffInSeconds()
    $auth = User::factory()->create();

    Carbon::setTestNowAndTimezone(now());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => Carbon::now()->subHours(26), // Started 2 days ago
    ]);

    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now()->subHours(13), // EXPIRED
        'kept_at' => Carbon::now(), // Not kept
        'deleted_at' => Carbon::now(),
    ]);

    Carbon::setTestNow(now()->addHours(14));
    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the recent message is still there
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('SoftDeleted messages that are expired but not kept are Deleted ', function () {

    // **Use subHours()  because  future dates causes negative diffInSeconds()
    $auth = User::factory()->create();

    Carbon::setTestNowAndTimezone(Carbon::now());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => Carbon::now()->subHours(26), // Started 2 days ago
    ]);

    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHours(13), // EXPIRED
        'kept_at' => null, // Not kept
        'deleted_at' => now()->subHours(13),
    ]);

    // Run the job
    DeleteExpiredMessagesJob::dispatch();

    // Assert the recent message is still there
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('messages WITHOUT delete Actions, that are expired, but not kept are Deleted ', function () {

    // **Use subHours()  because  future dates causes negative diffInSeconds()
    $auth = User::factory()->create();

    Carbon::setTestNowAndTimezone(now());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => Carbon::now()->subHours(26), // Started 2 days ago
    ]);

    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now()->subHours(13), // 13 hours ago, not expired
        'kept_at' => null, // Not kept
        'deleted_at' => Carbon::now(),
    ]);

    $recentMessage->deleteFor($auth);

    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the recent message is still there
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('messages WITH delete Actions, that are expired, and kept are Deleted ', function () {

    // **Use subHours()  because  future dates causes negative diffInSeconds()
    // Set up a conversation with disappearing messages
    $auth = User::factory()->create();

    Carbon::setTestNowAndTimezone(now());
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => Carbon::now()->subHours(26), // Started 2 days ago
    ]);

    $recentMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now()->subHours(13), // 13 hours ago, not expired
        'kept_at' => Carbon::now(), // Not kept
        'deleted_at' => null,
    ]);

    $recentMessage->deleteFor($auth);

    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the recent message is still there
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $recentMessage->id]);
});

test('test_kept_messages_are_not_deleted', function () {

    $auth = User::factory()->create();
    // Set up a conversation with disappearing messages
    Carbon::setTestNow(now()->subDays(4));
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => Carbon::now()->subDays(4), // Started 2 days ago
    ]);

    // Create a message that's kept
    Carbon::setTestNow(now()->subDays(2));
    $keptMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => Carbon::now(), // 1 day ago, expired
        'kept_at' => Carbon::now(), // Kept by user
    ]);

    // dd($conversation->get(['disappearing_duration','disappearing_started_at']), $conversation->messages()->get(['kept_at','created_at']));

    // Run the job
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the kept message is not deleted
    $this->assertDatabaseHas((new Message)->getTable(), ['id' => $keptMessage->id]);

});

test('messages_are_deleted_based_on_age_not_started_at', function () {
    $auth = User::factory()->create();

    // Set up a conversation with disappearing messages
    Carbon::setTestNow(now()->subDays(3)); // Fake time to 3 days ago
    $conversation = Conversation::factory()->withParticipants([$auth], ParticipantRole::OWNER)->create([
        'disappearing_duration' => 43200, // 12 hours in seconds
        'disappearing_started_at' => now()->subDays(3), // Started 3 days ago
    ]);

    // Create messages
    // Message created 2 days ago (older than duration, should be deleted)
    $expiredMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subDays(2),
        'kept_at' => null, // Not kept
    ]);

    // Message created 6 hours ago (newer than duration, should NOT be deleted)
    $newMessage = Message::factory()->sender($auth)->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHours(6),
        'kept_at' => null, // Not kept
    ]);

    // Run the job
    Carbon::setTestNow(now()); // Reset time to "now"
    $job = new DeleteExpiredMessagesJob;
    $job->handle();

    // Assert the expired message is deleted
    $this->assertDatabaseMissing((new Message)->getTable(), ['id' => $expiredMessage->id]);

    // Assert the new message is not deleted
    $this->assertDatabaseHas((new Message)->getTable(), ['id' => $newMessage->id]);
});
