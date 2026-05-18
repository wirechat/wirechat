<?php

namespace Wirechat\Wirechat\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class WirechatEncryption
{
    public function encryptString(?string $value): ?string
    {
        if (! $this->shouldEncrypt($value)) {
            return $value;
        }

        $compression = 'none';
        $plainValue = $value;

        if ($this->shouldCompress($value)) {
            $compressed = gzencode($value, $this->compressionLevel());

            if (is_string($compressed) && (! $this->onlyCompressIfSmaller() || strlen($compressed) < strlen($value))) {
                $plainValue = $compressed;
                $compression = 'gzip';
            }
        }

        return $this->prefix().base64_encode(json_encode([
            'v' => 1,
            'driver' => $this->driver(),
            'key' => $this->key(),
            'compression' => $compression,
            'payload' => Crypt::encryptString($plainValue),
        ], JSON_THROW_ON_ERROR));
    }

    public function decryptString(?string $value): ?string
    {
        if ($value === null || $value === '' || ! $this->isEncrypted($value)) {
            return $value;
        }

        $envelope = $this->decodeEnvelope($value);
        $payload = $envelope['payload'] ?? null;
        $compression = $envelope['compression'] ?? 'none';

        if (($envelope['v'] ?? null) !== 1 || ! is_string($payload)) {
            throw new DecryptException('Invalid Wirechat encrypted payload.');
        }

        $plainValue = Crypt::decryptString($payload);

        if ($compression === 'gzip') {
            $decompressed = gzdecode($plainValue);

            if (! is_string($decompressed)) {
                throw new DecryptException('Invalid Wirechat compressed payload.');
            }

            return $decompressed;
        }

        if ($compression !== 'none') {
            throw new DecryptException('Unsupported Wirechat compression mode.');
        }

        return $plainValue;
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, $this->prefix());
    }

    public function shouldEncrypt(?string $value): bool
    {
        return $value !== null
            && $value !== ''
            && $this->isEnabled()
            && ! $this->isEncrypted($value);
    }

    public function isEnabled(): bool
    {
        return (bool) config('wirechat.encryption.enabled', false);
    }

    public function shouldCompress(string $value): bool
    {
        return $this->compressionEnabled()
            && strlen($value) >= $this->compressionMinBytes();
    }

    protected function decodeEnvelope(string $value): array
    {
        $encoded = substr($value, strlen($this->prefix()));
        $json = base64_decode($encoded, true);

        if (! is_string($json)) {
            throw new DecryptException('Invalid Wirechat encrypted payload.');
        }

        try {
            $envelope = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new DecryptException('Invalid Wirechat encrypted payload.', previous: $exception);
        }

        if (! is_array($envelope)) {
            throw new DecryptException('Invalid Wirechat encrypted payload.');
        }

        return $envelope;
    }

    protected function prefix(): string
    {
        return (string) config('wirechat.encryption.prefix', 'wcenc:v1:');
    }

    protected function driver(): string
    {
        return (string) config('wirechat.encryption.driver', 'laravel');
    }

    protected function key(): string
    {
        return (string) config('wirechat.encryption.key', 'app');
    }

    protected function compressionEnabled(): bool
    {
        return (bool) config('wirechat.encryption.compression.enabled', true);
    }

    protected function compressionMinBytes(): int
    {
        return max(0, (int) config('wirechat.encryption.compression.min_bytes', 512));
    }

    protected function compressionLevel(): int
    {
        return max(0, min(9, (int) config('wirechat.encryption.compression.level', 6)));
    }

    protected function onlyCompressIfSmaller(): bool
    {
        return (bool) config('wirechat.encryption.compression.only_if_smaller', true);
    }
}
