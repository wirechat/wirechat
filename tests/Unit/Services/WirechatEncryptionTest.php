<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Wirechat\Wirechat\Facades\WirechatEncryption;

beforeEach(function () {
    config()->set('wirechat.encryption', [
        'enabled' => true,
        'compression' => [
            'enabled' => true,
            'min_bytes' => 512,
            'level' => 6,
            'require_smaller_output' => true,
        ],
    ]);
});

function wirechatEncryptionPayload(string $value): string
{
    $prefix = 'wcenc:v1:';

    return substr($value, strlen($prefix));
}

function wirechatEncryptedPayloadJson(string $value): array
{
    $json = base64_decode(wirechatEncryptionPayload($value), true);

    return json_decode((string) $json, true);
}

function wirechatUncompressibleMessage(): string
{
    $message = '';

    for ($i = 0; strlen($message) < 600; $i++) {
        $message .= hash('sha256', 'wirechat-'.$i, true);
    }

    return substr($message, 0, 600);
}

it('encrypts and decrypts plaintext strings', function () {
    $encrypted = WirechatEncryption::encryptString('hello world');

    expect($encrypted)
        ->not->toBe('hello world')
        ->and(str_starts_with($encrypted, 'wcenc:v1:'))->toBeTrue()
        ->and(WirechatEncryption::isEncrypted($encrypted))->toBeTrue()
        ->and(WirechatEncryption::decryptString($encrypted))->toBe('hello world');
});

it('passes plaintext through when decrypting unencrypted values', function () {
    expect(WirechatEncryption::decryptString('plain text'))->toBe('plain text');
});

it('reports whether encryption is enabled', function () {
    expect(WirechatEncryption::isEnabled())->toBeTrue();

    config()->set('wirechat.encryption.enabled', false);

    expect(WirechatEncryption::isEnabled())->toBeFalse();
});

it('does not double encrypt encrypted values', function () {
    $encrypted = WirechatEncryption::encryptString('hello world');

    expect(WirechatEncryption::encryptString($encrypted))->toBe($encrypted);
});

it('does not compress short messages', function () {
    $stored = WirechatEncryption::encryptStringForStorage('short message');
    $encryptedPayload = wirechatEncryptedPayloadJson($stored['body']);

    expect($encryptedPayload)
        ->not->toHaveKey('driver')
        ->not->toHaveKey('key')
        ->not->toHaveKey('payload')
        ->not->toHaveKey('compression')
        ->and($stored['meta']['encryption'])->toBe([
            'v' => 1,
            'compression' => 'none',
        ])
        ->and(WirechatEncryption::decryptStringFromStorage($stored['body'], $stored['meta']))->toBe('short message');
});

it('compresses long messages when smaller', function () {
    $message = str_repeat('Long support ticket message. ', 80);
    $stored = WirechatEncryption::encryptStringForStorage($message);

    expect($stored['meta']['encryption']['compression'])->toBe('gzip')
        ->and(WirechatEncryption::decryptStringFromStorage($stored['body'], $stored['meta']))->toBe($message);
});

it('skips compression when compressed output is larger and smaller output is required', function () {
    $message = wirechatUncompressibleMessage();
    $stored = WirechatEncryption::encryptStringForStorage($message);

    expect(strlen((string) gzencode($message, 6)))->toBeGreaterThan(strlen($message))
        ->and($stored['meta']['encryption']['compression'])->toBe('none')
        ->and(WirechatEncryption::decryptStringFromStorage($stored['body'], $stored['meta']))->toBe($message);
});

it('allows compression when smaller output is not required', function () {
    config()->set('wirechat.encryption.compression.require_smaller_output', false);

    $message = wirechatUncompressibleMessage();
    $stored = WirechatEncryption::encryptStringForStorage($message);

    expect(strlen((string) gzencode($message, 6)))->toBeGreaterThan(strlen($message))
        ->and($stored['meta']['encryption']['compression'])->toBe('gzip')
        ->and(WirechatEncryption::decryptStringFromStorage($stored['body'], $stored['meta']))->toBe($message);
});

it('detects encrypted values by prefix', function () {
    expect(WirechatEncryption::isEncrypted('wcenc:v1:not-real'))->toBeTrue()
        ->and(WirechatEncryption::isEncrypted('not encrypted'))->toBeFalse();
});

it('throws when decrypting malformed encrypted payloads', function () {
    WirechatEncryption::decryptString('wcenc:v1:not-base64');
})->throws(DecryptException::class);
