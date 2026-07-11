<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Wirechat\Wirechat\Support\AttachmentMeta;

it('stores image dimensions from uploaded files', function () {
    $image = UploadedFile::fake()->image('photo.png', 640, 480);

    $meta = AttachmentMeta::fromUploadedFile($image, 'image/png')->toArray();

    expect($meta)
        ->toHaveKey('size')
        ->toMatchArray([
            'image' => [
                'width' => 640,
                'height' => 480,
                'orientation' => 'landscape',
                'aspect_ratio' => 1.3333,
            ],
        ]);
});

it('stores displayed dimensions for rotated video files', function () {
    $finder = new ExecutableFinder;
    $ffmpeg = $finder->find('ffmpeg');
    $ffprobe = $finder->find('ffprobe');

    if (! $ffmpeg || ! $ffprobe) {
        $this->markTestSkipped('ffmpeg and ffprobe are required for this test.');
    }

    $directory = storage_path('framework/testing/attachment-meta');
    $basePath = $directory.'/base.mp4';
    $rotatedPath = $directory.'/rotated.mp4';

    File::ensureDirectoryExists($directory);
    File::delete([$basePath, $rotatedPath]);

    $create = new Process([
        $ffmpeg,
        '-y',
        '-f',
        'lavfi',
        '-i',
        'color=c=black:s=320x180:d=0.1',
        '-c:v',
        'libx264',
        '-pix_fmt',
        'yuv420p',
        $basePath,
    ]);
    $create->setTimeout(10);
    $create->run();

    if (! $create->isSuccessful()) {
        $this->markTestSkipped('Could not create a video fixture.');
    }

    $rotate = new Process([
        $ffmpeg,
        '-y',
        '-display_rotation',
        '90',
        '-i',
        $basePath,
        '-c',
        'copy',
        $rotatedPath,
    ]);
    $rotate->setTimeout(10);
    $rotate->run();

    if (! $rotate->isSuccessful()) {
        $this->markTestSkipped('Could not create a rotated video fixture.');
    }

    $meta = AttachmentMeta::fromLocalPath($rotatedPath, 'video/mp4', 123, isVideo: true)->toArray();

    expect($meta)
        ->toMatchArray([
            'size' => 123,
            'video' => [
                'width' => 180,
                'height' => 320,
                'orientation' => 'portrait',
                'aspect_ratio' => 0.5625,
                'rotation' => 90,
            ],
        ]);
});
