<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->composerPath = base_path('composer.json');
    $this->authPath = base_path('auth.json');
    $this->originalComposer = File::exists($this->composerPath) ? File::get($this->composerPath) : null;
    $this->originalAuth = File::exists($this->authPath) ? File::get($this->authPath) : null;

    File::put($this->composerPath, json_encode([
        'require' => [
            'php' => '^8.2',
            'wirechat/wirechat' => '^0.6',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    if (File::exists($this->authPath)) {
        File::delete($this->authPath);
    }
});

afterEach(function () {
    if ($this->originalComposer !== null) {
        File::put($this->composerPath, $this->originalComposer);
    } elseif (File::exists($this->composerPath)) {
        File::delete($this->composerPath);
    }

    if ($this->originalAuth !== null) {
        File::put($this->authPath, $this->originalAuth);
    } elseif (File::exists($this->authPath)) {
        File::delete($this->authPath);
    }
});

it('configures composer credentials and repository without installing pro when skipped', function () {
    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
        '--fingerprint' => 'fingerprint',
        '--skip-install' => true,
    ])
        ->expectsOutput('[✓] Composer credentials configured.')
        ->expectsOutput('Skipped package installation.')
        ->assertExitCode(0);

    $auth = json_decode(File::get($this->authPath), true, 512, JSON_THROW_ON_ERROR);
    $composer = json_decode(File::get($this->composerPath), true, 512, JSON_THROW_ON_ERROR);

    expect($auth['http-basic']['wirechat.composer.sh'])->toBe([
        'username' => 'admin@example.com',
        'password' => 'license-key:fingerprint',
    ])->and($composer['repositories'])->toContain([
        'type' => 'composer',
        'url' => 'https://wirechat.composer.sh',
    ]);
});

it('prints manual composer commands when package replacement is declined', function () {
    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
    ])
        ->expectsOutput('[✓] Composer credentials configured.')
        ->expectsConfirmation('Wirechat Pro replaces wirechat/wirechat because both packages use the same namespace. Continue with the Composer package replacement?', 'no')
        ->expectsOutput('Run these commands manually when you are ready:')
        ->expectsOutput('composer remove wirechat/wirechat --no-update')
        ->expectsOutput('composer require wirechat/wirechat-pro -W')
        ->assertExitCode(0);
});
