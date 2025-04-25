<?php

use Illuminate\Support\Facades\Config as FacadesConfig;
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

    // Set the test disk as default for this    test
    $this->app['config']->set('wirechat.attachments.storage_disk', 'test_disk');

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

it('generaes temporary URL when disk_visibility is private in wirechat', function () {

    // Set the test disk as default for this
    FacadesConfig::set('wirechat.attachments.storage_disk', 's3');
    FacadesConfig::set('wirechat.attachments.disk_visibility', 'private');

    Storage::fake('s3');

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

    Storage::disk('s3')->put($attachmentPath, 'test content');

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
    expect($url)->toContain('expiration=');
    // expect($url)->toContain('signature=');

    // Clean up (optional)
    Storage::disk('s3')->delete($attachmentPath);
});

it('does not generate temporary URL when disk_visibility is public in wirechat', function () {

    // Set the test disk as default for this    test
    FacadesConfig::set('wirechat.attachments.storage_disk', 'public');
    FacadesConfig::set('wirechat.attachments.disk_visibility', 'public');

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

    Storage::disk('local')->put($attachmentPath, 'test content');

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
    expect($url)->not->toContain('expiration=');
    expect($url)->not->toContain('signature=');

    // Clean up (optional)
    Storage::disk('local')->delete($attachmentPath);
});
