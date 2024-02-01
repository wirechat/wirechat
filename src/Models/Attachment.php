<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['file_path', 'file_name', 'mime_type'];


    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.attachments_table');

        parent::__construct($attributes);
    }


    public function message()
    {
        return $this->hasOne(Message::class);
    }

    public function getCleanMimeTypeAttribute()
    {
        return explode('/', $this->mime_type)[1] ?? 'unknown';
    }


}
