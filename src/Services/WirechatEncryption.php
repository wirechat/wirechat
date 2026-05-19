<?php

namespace Wirechat\Wirechat\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class WirechatEncryption
{
    private const VERSION = 1;

    private const PREFIX = 'wcenc:v'.self::VERSION.':';

    private const COMPRESSION_NONE = 'none';

    private const COMPRESSION_GZIP = 'gzip';

    private const DEFAULT_REQUIRE_SMALLER_OUTPUT = true;

    public function encryptString(?string $value): ?string
    {
        if (! $this->shouldEncrypt($value)) {
            return $value;
        }

        return $this->prefix().Crypt::encryptString($value);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array{body: string|null, meta: array<string, mixed>|null}
     */
    public function encryptStringForStorage(?string $value, ?array $meta = null): array
    {
        if (! $this->shouldEncrypt($value)) {
            return [
                'body' => $value,
                'meta' => $this->isEncrypted($value) ? $meta : $this->withoutEncryptionMeta($meta),
            ];
        }

        $compression = self::COMPRESSION_NONE;
        $plainValue = $value;

        if ($this->shouldCompress($value)) {
            $compressed = $this->compress($value);

            if (is_string($compressed) && (! $this->requiresSmallerCompressionOutput() || strlen($compressed) < strlen($value))) {
                $plainValue = $compressed;
                $compression = self::COMPRESSION_GZIP;
            }
        }

        return [
            'body' => $this->prefix().Crypt::encryptString($plainValue),
            'meta' => $this->withEncryptionMeta($meta, $compression),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function decryptString(?string $value, ?array $meta = null): ?string
    {
        return $this->decryptStringFromStorage($value, $meta);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function decryptStringFromStorage(?string $value, ?array $meta = null): ?string
    {
        if ($value === null || $value === '' || ! str_starts_with($value, $this->prefix())) {
            return $value;
        }

        $payload = substr($value, strlen($this->prefix()));
        $hasEncryptionMeta = $this->encryptionMeta($meta) !== null;

        if (! $this->hasLaravelEncryptedPayloadShape($payload)) {
            if ($hasEncryptionMeta) {
                throw new DecryptException('Invalid Wirechat encrypted payload.');
            }

            return $value;
        }

        if (! $hasEncryptionMeta && ! $this->isEncrypted($value)) {
            return $value;
        }

        return $this->decryptPayload(
            $payload,
            $this->compressionFromMeta($meta),
        );
    }

    protected function decryptPayload(string $payload, string $compression): string
    {
        if (! in_array($compression, [self::COMPRESSION_NONE, self::COMPRESSION_GZIP], true)) {
            throw new DecryptException('Unsupported Wirechat compression mode.');
        }

        $plainValue = Crypt::decryptString($payload);

        if ($compression === self::COMPRESSION_GZIP) {
            return $this->decompress($plainValue);
        }

        return $plainValue;
    }

    public function isEncrypted(?string $value): bool
    {
        if (! is_string($value) || ! str_starts_with($value, $this->prefix())) {
            return false;
        }

        $payload = substr($value, strlen($this->prefix()));

        return $this->hasLaravelEncryptedPayloadShape($payload)
            && $this->canDecryptPayload($payload);
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
            && $this->supportsCompression()
            && strlen($value) >= $this->compressionMinBytes();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function compressionFromMeta(?array $meta): string
    {
        $encryptionMeta = $this->encryptionMeta($meta);

        if ($encryptionMeta === null) {
            return self::COMPRESSION_NONE;
        }

        if (($encryptionMeta['v'] ?? null) !== self::VERSION) {
            throw new DecryptException('Invalid Wirechat encrypted payload.');
        }

        $compression = $encryptionMeta['compression'] ?? self::COMPRESSION_NONE;

        if (! is_string($compression)) {
            throw new DecryptException('Invalid Wirechat encrypted payload.');
        }

        return $compression;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>|null
     */
    protected function encryptionMeta(?array $meta): ?array
    {
        $encryptionMeta = $meta['encryption'] ?? null;

        return is_array($encryptionMeta) ? $encryptionMeta : null;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>
     */
    protected function withEncryptionMeta(?array $meta, string $compression): array
    {
        $meta ??= [];
        $meta['encryption'] = [
            'v' => self::VERSION,
            'compression' => $compression,
        ];

        return $meta;
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>|null
     */
    protected function withoutEncryptionMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        unset($meta['encryption']);

        return $meta === [] ? null : $meta;
    }

    protected function hasLaravelEncryptedPayloadShape(string $payload): bool
    {
        $decoded = $this->decodeLaravelEncryptedPayload($payload);

        if (! is_array($decoded)
            || ! is_string($decoded['iv'] ?? null)
            || ! is_string($decoded['value'] ?? null)
            || ! is_string($decoded['mac'] ?? null)
            || ! array_key_exists('tag', $decoded)
            || ! is_string($decoded['tag'])
        ) {
            return false;
        }

        $iv = base64_decode($decoded['iv'], true);
        $value = base64_decode($decoded['value'], true);
        $ivLength = $this->cipherIvLength();

        if (! is_string($iv) || $value === false || $ivLength === null || strlen($iv) !== $ivLength) {
            return false;
        }

        if ($this->cipherUsesAead()) {
            $tag = base64_decode($decoded['tag'], true);

            return $decoded['mac'] === ''
                && is_string($tag)
                && strlen($tag) === 16;
        }

        return $decoded['tag'] === ''
            && strlen($decoded['mac']) === 64
            && ctype_xdigit($decoded['mac']);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeLaravelEncryptedPayload(string $payload): ?array
    {
        $json = base64_decode($payload, true);

        if (! is_string($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function canDecryptPayload(string $payload): bool
    {
        try {
            Crypt::decryptString($payload);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    protected function compress(string $value): ?string
    {
        if (! $this->supportsCompression()) {
            return null;
        }

        try {
            $compressed = gzencode($value, $this->compressionLevel());
        } catch (Throwable) {
            return null;
        }

        return is_string($compressed) ? $compressed : null;
    }

    protected function decompress(string $value): string
    {
        if (! function_exists('gzdecode')) {
            throw new DecryptException('Wirechat encrypted payload requires gzip decompression, but the PHP zlib extension is not available.');
        }

        try {
            $decompressed = gzdecode($value);
        } catch (Throwable $exception) {
            throw new DecryptException('Invalid Wirechat compressed payload using gzip compression.', 0, $exception);
        }

        if (! is_string($decompressed)) {
            throw new DecryptException('Invalid Wirechat compressed payload using gzip compression.');
        }

        return $decompressed;
    }

    protected function supportsCompression(): bool
    {
        return function_exists('gzencode') && function_exists('gzdecode');
    }

    protected function cipher(): string
    {
        return strtolower((string) config('app.cipher', 'AES-256-CBC'));
    }

    protected function cipherUsesAead(): bool
    {
        return in_array($this->cipher(), ['aes-128-gcm', 'aes-256-gcm'], true);
    }

    protected function cipherIvLength(): ?int
    {
        try {
            $length = openssl_cipher_iv_length($this->cipher());
        } catch (Throwable) {
            return null;
        }

        return is_int($length) ? $length : null;
    }

    protected function prefix(): string
    {
        return self::PREFIX;
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

    protected function requiresSmallerCompressionOutput(): bool
    {
        return (bool) config('wirechat.encryption.compression.require_smaller_output', self::DEFAULT_REQUIRE_SMALLER_OUTPUT);
    }
}
