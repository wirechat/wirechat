<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->composerPath = base_path('composer.json');
    $this->authPath = base_path('auth.json');
    $this->activationUrl = 'https://corepine.dev/api/licenses/activate';
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

function fakeSuccessfulCorepineActivation(string $activationUrl): void
{
    Http::fake([
        $activationUrl => Http::response([
            'status' => 'active',
            'package' => 'wirechat/wirechat-pro',
            'license_key' => 'license-key',
            'composer_host' => 'composer.corepine.dev',
            'activation' => [
                'fingerprint' => 'example.com',
            ],
        ], 200),
    ]);
}

it('configures composer credentials and repository without installing pro when skipped', function () {
    fakeSuccessfulCorepineActivation($this->activationUrl);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
        '--fingerprint' => 'example.com',
        '--skip-install' => true,
    ])
        ->expectsOutput('[✓] License activated with Corepine.')
        ->expectsOutput('[✓] Composer credentials configured.')
        ->expectsOutput('Skipped package installation.')
        ->assertExitCode(0);

    $auth = json_decode(File::get($this->authPath), true, 512, JSON_THROW_ON_ERROR);
    $composer = json_decode(File::get($this->composerPath), true, 512, JSON_THROW_ON_ERROR);

    expect($auth['http-basic']['composer.corepine.dev'])->toBe([
        'username' => 'admin@example.com',
        'password' => 'license-key:example.com',
    ])->and($auth['http-basic'])->not->toHaveKey('wirechat.composer.sh')
        ->and($composer['repositories'])->toContain([
            'type' => 'composer',
            'url' => 'https://composer.corepine.dev',
        ]);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === $this->activationUrl
            && $payload['email'] === 'admin@example.com'
            && $payload['license_key'] === 'license-key'
            && $payload['package_name'] === 'wirechat/wirechat-pro'
            && $payload['fingerprint'] === 'example.com'
            && $payload['app_name'] === config('app.name')
            && $payload['app_url'] === config('app.url')
            && $payload['environment'] === app()->environment()
            && $payload['metadata'] === [
                'source_package' => 'wirechat/wirechat',
                'source_command' => 'wirechat:activate',
            ];
    });
});

it('prints manual composer commands when package replacement is declined', function () {
    fakeSuccessfulCorepineActivation($this->activationUrl);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
    ])
        ->expectsOutput('[✓] License activated with Corepine.')
        ->expectsOutput('[✓] Composer credentials configured.')
        ->expectsConfirmation('Wirechat Pro replaces wirechat/wirechat because both packages use the same namespace. Continue with the Composer package replacement?', 'no')
        ->expectsOutput('Run these commands manually when you are ready:')
        ->expectsOutput('composer remove wirechat/wirechat --no-update')
        ->expectsOutput('composer require wirechat/wirechat-pro -W')
        ->assertExitCode(0);
});

it('removes legacy composer repository and auth entries', function () {
    fakeSuccessfulCorepineActivation($this->activationUrl);

    File::put($this->authPath, json_encode([
        'http-basic' => [
            'wirechat.composer.sh' => [
                'username' => 'old@example.com',
                'password' => 'old-license-key',
            ],
            'repo.packagist.com' => [
                'username' => 'token',
                'password' => 'secret',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    File::put($this->composerPath, json_encode([
        'require' => [
            'php' => '^8.2',
            'wirechat/wirechat' => '^0.6',
        ],
        'repositories' => [
            [
                'type' => 'composer',
                'url' => 'https://wirechat.composer.sh',
            ],
            [
                'type' => 'path',
                'url' => '../local-package',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
        '--skip-install' => true,
    ])->assertExitCode(0);

    $auth = json_decode(File::get($this->authPath), true, 512, JSON_THROW_ON_ERROR);
    $composer = json_decode(File::get($this->composerPath), true, 512, JSON_THROW_ON_ERROR);

    expect($auth['http-basic'])->not->toHaveKey('wirechat.composer.sh')
        ->and($auth['http-basic'])->toHaveKey('composer.corepine.dev')
        ->and($auth['http-basic'])->toHaveKey('repo.packagist.com')
        ->and(array_column($composer['repositories'], 'url'))->toContain('https://composer.corepine.dev')
        ->and(array_column($composer['repositories'], 'url'))->toContain('../local-package')
        ->and(array_column($composer['repositories'], 'url'))->not->toContain('https://wirechat.composer.sh');
});

it('does not write composer credentials when Corepine rejects an invalid license', function () {
    $originalComposer = File::get($this->composerPath);

    Http::fake([
        $this->activationUrl => Http::response([
            'message' => 'Invalid license credentials.',
        ], 401),
    ]);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'bad-license-key',
        '--skip-install' => true,
    ])
        ->expectsOutput('Invalid license credentials.')
        ->assertExitCode(1);

    expect(File::exists($this->authPath))->toBeFalse()
        ->and(File::get($this->composerPath))->toBe($originalComposer);
});

it('does not install pro when the activation limit is reached', function () {
    $originalComposer = File::get($this->composerPath);

    Http::fake([
        $this->activationUrl => Http::response([
            'message' => 'Activation limit reached for this license.',
        ], 403),
    ]);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
    ])
        ->expectsOutput('Activation limit reached for this license.')
        ->assertExitCode(1);

    expect(File::exists($this->authPath))->toBeFalse()
        ->and(File::get($this->composerPath))->toBe($originalComposer);
});

it('returns a clear error when Corepine requires an activation domain', function () {
    $originalComposer = File::get($this->composerPath);

    Http::fake([
        $this->activationUrl => Http::response([
            'message' => 'An activation domain is required for this license.',
        ], 422),
    ]);

    $this->artisan('wirechat:activate', [
        '--email' => 'admin@example.com',
        '--license' => 'license-key',
        '--skip-install' => true,
    ])
        ->expectsOutput('An activation domain is required for this license.')
        ->assertExitCode(1);

    expect(File::exists($this->authPath))->toBeFalse()
        ->and(File::get($this->composerPath))->toBe($originalComposer);
});
