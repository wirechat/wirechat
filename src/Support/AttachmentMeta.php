<?php

namespace Wirechat\Wirechat\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class AttachmentMeta
{
    /**
     * @param  array<string, mixed>|null  $image
     * @param  array<string, mixed>|null  $video
     */
    private function __construct(
        private readonly ?int $size,
        private readonly ?array $image = null,
        private readonly ?array $video = null,
    ) {}

    public static function fromUploadedFile(UploadedFile $file, string $mimeType): self
    {
        $path = $file->getRealPath();

        return new self(
            size: $file->getSize(),
            image: Str::startsWith($mimeType, 'image/') ? self::image($path) : null,
            video: Str::startsWith($mimeType, 'video/') ? self::video($path) : null,
        );
    }

    public static function fromLocalPath(string $path, string $mimeType, ?int $size = null, bool $isImage = false, bool $isVideo = false): self
    {
        return new self(
            size: $size,
            image: ($isImage || Str::startsWith($mimeType, 'image/')) ? self::image($path) : null,
            video: ($isVideo || Str::startsWith($mimeType, 'video/')) ? self::video($path) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $meta = [
            'size' => $this->size,
        ];

        if ($this->image !== null) {
            $meta['image'] = $this->image;
        }

        if ($this->video !== null) {
            $meta['video'] = $this->video;
        }

        return $meta;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function image(string|false|null $path): ?array
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        try {
            $size = getimagesize($path);

            if (! is_array($size)) {
                return null;
            }

            $width = (int) ($size[0] ?? 0);
            $height = (int) ($size[1] ?? 0);

            return self::dimensions($width, $height);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function video(string|false|null $path): ?array
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $ffprobe = (new ExecutableFinder)->find('ffprobe');

        if (! is_string($ffprobe) || $ffprobe === '') {
            return null;
        }

        try {
            $process = new Process([
                $ffprobe,
                '-v',
                'error',
                '-select_streams',
                'v:0',
                '-show_entries',
                'stream=width,height:stream_tags=rotate:stream_side_data=rotation',
                '-of',
                'json',
                $path,
            ]);

            $process->setTimeout(5);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $payload = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            $stream = $payload['streams'][0] ?? null;

            if (! is_array($stream)) {
                return null;
            }

            $width = (int) ($stream['width'] ?? 0);
            $height = (int) ($stream['height'] ?? 0);

            if ($width <= 0 || $height <= 0) {
                return null;
            }

            $rotation = self::rotation($stream);
            [$displayWidth, $displayHeight] = self::displayDimensions($width, $height, $rotation);

            $video = self::dimensions($displayWidth, $displayHeight);

            if ($video === null) {
                return null;
            }

            if ($rotation !== null) {
                $video['rotation'] = $rotation;
            }

            return $video;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function dimensions(int $width, int $height): ?array
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return [
            'width' => $width,
            'height' => $height,
            'orientation' => $height > $width ? 'portrait' : ($width > $height ? 'landscape' : 'square'),
            'aspect_ratio' => round($width / $height, 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $stream
     */
    private static function rotation(array $stream): ?int
    {
        $candidates = [
            $stream['tags']['rotate'] ?? null,
        ];

        foreach (($stream['side_data_list'] ?? []) as $sideData) {
            if (is_array($sideData) && array_key_exists('rotation', $sideData)) {
                $candidates[] = $sideData['rotation'];
            }
        }

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) round((float) $candidate);
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function displayDimensions(int $width, int $height, ?int $rotation): array
    {
        $normalizedRotation = $rotation === null
            ? 0
            : (($rotation % 360) + 360) % 360;

        if (in_array($normalizedRotation, [90, 270], true)) {
            return [$height, $width];
        }

        return [$width, $height];
    }
}
