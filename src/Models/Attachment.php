<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Facades\WireChat;

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

    protected $fillable = ['attachable_id', 'attachable_type', 'file_path', 'file_name', 'mime_type', 'url', 'original_name'];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('attachments');

        parent::__construct($attributes);
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\AttachmentFactory::new();
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

        $disk = Storage::disk(WireChat::storageDisk());

        $config = config('wirechat.attachments');

        // If the disk is set to private, generate a temporary URL
        if (($config['disk_visibility'] ?? 'public') === 'private') {
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
}
