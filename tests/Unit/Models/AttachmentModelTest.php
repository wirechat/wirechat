<?php

use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Message;
use Workbench\App\Models\User;

it('tests attachment URL generation with custom test_disk', function () {
    // Dynamically configure the "test_disk" disk for testing
    $this->app['config']->set('filesystems.disks.test_disk', [
        'driver' => 'local',
        'root' => storage_path('app/test_disk'), // Directory for the test disk
        'url' => env('APP_URL').'/storage/test_disk', // Custom URL for the test disk
        'visibility' => 'public',
    ]);
    $this->app['config']->set('filesystems.default', 'test_disk'); // Set the test disk as default for this test

    // Create two users (one will act as the sender)
    $auth = User::factory()->create();
    $user1 = User::factory()->create(['name' => 'iam user 1']);

    // Create a conversation between the two users
    $conversation = $auth->createConversationWith($user1);

    // Create a message with attachment type
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sendable_type' => get_class($auth),
        'sendable_id' => $auth->id,
        'type' => 'attachment',
    ]);

    // Create an attachment for the message on the "test_disk"
    $attachmentPath = 'test-attachment.txt';
    Storage::disk('test_disk')->put($attachmentPath, 'test content');

    // Associate the attachment with the message
    $createdAttachment = Attachment::factory()->for($message, 'attachable')->create([
        'file_path' => $attachmentPath,
        'file_name' => basename($attachmentPath),
        'original_name' => 'test-attachment.txt',
        'mime_type' => 'text/plain',
        'url' => Storage::url($attachmentPath), // This should return a URL based on "test_disk"
    ]);

    // Retrieve the URL of the attachment
    $url = $createdAttachment->url;

    // Assert the URL is correctly formed
    expect($url)->toContain(env('APP_URL').'/storage/test_disk/'.$attachmentPath);

    // Clean up (optional)
    Storage::disk('test_disk')->delete($attachmentPath);
});
