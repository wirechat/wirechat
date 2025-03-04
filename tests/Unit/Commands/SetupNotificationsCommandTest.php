<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up existing files for isolation
    if (File::exists(public_path('js/wirechat/sw.js'))) {
        File::delete(public_path('js/wirechat/sw.js'));
    }
    if (File::exists(public_path('sw.js'))) {
        File::delete(public_path('sw.js'));
    }
    if (! File::exists(public_path('js/wirechat'))) {
        File::makeDirectory(public_path('js/wirechat'), 0755, true);
    }
});

// Test that files are created if they don't exist
it('creates the Wirechat and main service worker files when they do not exist', function () {
    // For testing, simulate stub file content

    // Create dummy stub files
    $mainStubPath = __DIR__.'/../../../stubs/MainServiceWorkerJsScript.stub';
    $serviceStubPath = __DIR__.'/../../../stubs/ServiceWorkerJsScript.stub';

    $mainStubContent = File::get($mainStubPath);
    $serviceStubContent = File::get($serviceStubPath);

    if (! File::exists(dirname($mainStubPath))) {
        File::makeDirectory(dirname($mainStubPath), 0755, true);
    }
    File::put($mainStubPath, $mainStubContent);
    File::put($serviceStubPath, $serviceStubContent);

    $this->artisan('wirechat:setup-notifications')
        ->expectsOutput('✅ Wirechat notifications setup complete!')
        ->assertExitCode(0);

    expect(File::exists(public_path('js/wirechat/sw.js')))->toBeTrue();
    expect(File::exists(public_path('sw.js')))->toBeTrue();
    // expect(File::get(public_path('js/wirechat/sw.js')))->toEqual($serviceStubContent);
    // expect(File::get(public_path('sw.js')))->toEqual($mainStubContent);

});

// Test that if the Wirechat service worker file exists and the user declines to overwrite it, its content remains unchanged
it('does not overwrite the existing Wirechat service worker file when user declines', function () {
    // Arrange: create a dummy file with known content
    $originalContent = 'original service worker content';

    File::put(public_path('js/wirechat/sw.js'), $originalContent);

    $this->artisan('wirechat:setup-notifications')
        ->expectsConfirmation(
            'Wirechat service worker script already exists at `js/wirechat/sw.js`. Do you want to overwrite it?',
            'no'
        )
        ->expectsOutput('Existing Wirechat service worker was not overwritten.');

    expect(File::get(public_path('js/wirechat/sw.js')))->toEqual($originalContent);
});

// Test that if the Wirechat service worker file exists and the user confirms to overwrite it, the file is replaced with stub content
it('overwrites the existing Wirechat service worker file when user confirms', function () {
    // Arrange: create a dummy file with known content
    $originalContent = 'original service worker content';
    File::put(public_path('js/wirechat/sw.js'), $originalContent);

    // Create dummy stub file for ServiceWorkerJsScript.stub

    $stubPath = __DIR__.'/../../../stubs/ServiceWorkerJsScript.stub';

    $stubContent = File::get($stubPath);
    if (! File::exists(dirname($stubPath))) {
        File::makeDirectory(dirname($stubPath), 0755, true);
    }
    File::put($stubPath, $stubContent);

    $this->artisan('wirechat:setup-notifications')
        ->expectsConfirmation(
            'Wirechat service worker script already exists at `js/wirechat/sw.js`. Do you want to overwrite it?',
            'yes'
        )
        ->expectsOutput('✅ Wirechat service worker script successfully overwritten at `js/wirechat/sw.js`.');

    expect(File::get(public_path('js/wirechat/sw.js')))->toEqual($stubContent);

});

// Test that if the main service worker file already exists, it is not overwritten
it('does not overwrite the existing main service worker file (sw.js)', function () {
    // Arrange: create a dummy sw.js file with known content

    $stubPath = __DIR__.'/../../../stubs/MainServiceWorkerJsScript.stub';

    $originalContent = File::get($stubPath);
    File::put(public_path('sw.js'), $originalContent);

    $this->artisan('wirechat:setup-notifications')
        ->expectsOutput('⚠️ A service worker (sw.js) already exists in the public directory.');

    expect(File::get(public_path('sw.js')))->toEqual($originalContent);
});
