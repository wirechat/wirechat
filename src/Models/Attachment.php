<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $file_path
 * @property string $file_name
 * @property string $original_name
 * @property string $url
 * @property string $mime_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $attachable
 * @property-read string $clean_mime_type
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUrl($value)
 *
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    use HasFactory;

    protected const GENERIC_MIME_TYPES = [
        'application/octet-stream',
        'application/x-empty',
        'inode/x-empty',
    ];

    protected const IMAGE_EXTENSIONS = [
        'png',
        'jpg',
        'jpeg',
        'gif',
        'webp',
        'bmp',
        'svg',
        'avif',
        'heic',
        'heif',
    ];

    protected const VIDEO_EXTENSIONS = [
        'mp4',
        'mov',
        'avi',
        'wmv',
        'webm',
        'm4v',
        'mkv',
    ];

    protected $fillable = ['attachable_id', 'attachable_type', 'file_path', 'file_name', 'mime_type', 'url', 'original_name'];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('attachments');

        parent::__construct($attributes);
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Wirechat\Wirechat\Workbench\Database\Factories\AttachmentFactory::new();
    }

    protected static function booted(): void
    {
        static::deleted(function (Attachment $media) {
            $disk = Wirechat::storage()->disk();

            if (Storage::disk($disk)->exists($media->file_path)) {
                Storage::disk($disk)->delete($media->file_path);
            }
        });
    }

    /**
     * Get the full URL of the attachment based on the configured storage disk.
     *
     * This attribute dynamically generates the correct file URL, whether stored locally
     * or on an external disk like S3. If the file path is not set, it returns null.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $this->generateUrl($attributes['file_path'] ?? null)
        );
    }

    /**
     * Generate the URL for the attachment.
     *
     * @param  string|null  $path  The file path of the attachment.
     * @return string|null The generated URL for the attachment.
     */
    protected function generateUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $diskVisibility = Wirechat::storage()->visibility();
        $storageDisk = Wirechat::storage()->disk();

        $disk = Storage::disk($storageDisk);

        // If the disk is set to private, generate a temporary URL
        if (($diskVisibility) === 'private') {
            return $disk->temporaryUrl($path, now()->addMinutes(5));
        }

        return $disk->url($path);
    }

    /**
     * Get the attachable model instance.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCleanMimeTypeAttribute(): string
    {
        return explode('/', $this->mime_type)[1] ?? 'unknown';
    }

    public static function resolveMimeType(UploadedFile $file, ?string $storedPath = null, ?string $disk = null): string
    {
        $mimeType = $file->getMimeType();

        if (static::hasSpecificMimeType($mimeType)) {
            return $mimeType;
        }

        if ($storedPath && $disk) {
            $storedMimeType = Storage::disk($disk)->mimeType($storedPath);

            if (is_string($storedMimeType) && static::hasSpecificMimeType($storedMimeType)) {
                return $storedMimeType;
            }
        }

        $extension = Str::of($file->getClientOriginalName())->afterLast('.')->lower()->value();

        if ($extension !== '') {
            $guessedMimeType = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;

            if ($guessedMimeType) {
                return $guessedMimeType;
            }
        }

        return $mimeType ?: 'application/octet-stream';
    }

    public function isImage(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'image/')
            || $this->hasExtension(static::IMAGE_EXTENSIONS);
    }

    public function isVideo(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'video/')
            || $this->hasExtension(static::VIDEO_EXTENSIONS);
    }

    protected static function hasSpecificMimeType(?string $mimeType): bool
    {
        return filled($mimeType) && ! in_array($mimeType, static::GENERIC_MIME_TYPES, true);
    }

    protected function hasExtension(array $extensions): bool
    {
        $extension = Str::of($this->original_name ?: $this->file_name)
            ->afterLast('.')
            ->lower()
            ->value();

        return $extension !== '' && in_array($extension, $extensions, true);
    }
}
