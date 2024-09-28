<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Facades\WireChat;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['file_path', 'file_name', 'mime_type','url','original_name'];


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



    public function message()
    {
        return $this->hasOne(Message::class);
    }

    public function getCleanMimeTypeAttribute()
    {
        return explode('/', $this->mime_type)[1] ?? 'unknown';
    }


}
