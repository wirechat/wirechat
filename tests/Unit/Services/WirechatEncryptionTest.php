<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Wirechat\Wirechat\Facades\WirechatEncryption;

beforeEach(function () {
    config()->set('wirechat.encryption', [
        'enabled' => true,
        'prefix' => 'wcenc:v1:',
        'driver' => 'laravel',
        'key' => 'app',
        'compression' => [
            'enabled' => true,
            'min_bytes' => 512,
            'level' => 6,
            'only_if_smaller' => true,
        ],
    ]);
});

function wirechatEncryptionEnvelope(string $value): array
{
    $prefix = (string) config('wirechat.encryption.prefix');
    $json = base64_decode(substr($value, strlen($prefix)), true);

    return json_decode($json, true);
}

it('encrypts and decrypts plaintext strings', function () {
    $encrypted = WirechatEncryption::encryptString('hello world');

    expect($encrypted)
        ->not->toBe('hello world')
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
    $encrypted = WirechatEncryption::encryptString('short message');
    $envelope = wirechatEncryptionEnvelope($encrypted);

    expect($envelope['compression'])->toBe('none')
        ->and(WirechatEncryption::decryptString($encrypted))->toBe('short message');
});

it('compresses long messages when smaller', function () {
    $message = str_repeat('Long support ticket message. ', 80);
    $encrypted = WirechatEncryption::encryptString($message);
    $envelope = wirechatEncryptionEnvelope($encrypted);

    expect($envelope['compression'])->toBe('gzip')
        ->and(WirechatEncryption::decryptString($encrypted))->toBe($message);
});

it('detects encrypted values by prefix', function () {
    expect(WirechatEncryption::isEncrypted('wcenc:v1:not-real'))->toBeTrue()
        ->and(WirechatEncryption::isEncrypted('not encrypted'))->toBeFalse();
});

it('throws when decrypting malformed encrypted payloads', function () {
    WirechatEncryption::decryptString('wcenc:v1:not-base64');
})->throws(DecryptException::class);
