<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\User;

it('deletes attachment from storage when message is deleted', function () {
    $auth = User::factory()->create();

    Storage::fake(config('wirechat.attachments.storage_disk', 'public'));
    $attachment = UploadedFile::fake()->image('file.png');

    // save photo to disk
    //   $path = $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk', 'public'));

    $this->actingAs($auth);
    // create attachment

    // create message
    $group = $auth->createGroup(name: 'test group', photo: $attachment)->group;

    $attachment = $group->cover;

    // assert
    expect($group->cover->first())->not->toBe(null);
    Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertExists($attachment->file_path);

    // delete message
    $group->forceDelete();

    expect($group->cover()->first())->toBe(null);
    // assert
    Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->assertMissing($attachment->file_path);

});
