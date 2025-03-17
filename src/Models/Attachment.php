<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Facades\WireChat;

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
            get: fn (mixed $value, array $attributes) => isset($attributes['file_path']) ? Storage::disk(WireChat::storageDisk())->url($attributes['file_path']) : null,
        );
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function getCleanMimeTypeAttribute()
    {
        return explode('/', $this->mime_type)[1] ?? 'unknown';
    }
}
