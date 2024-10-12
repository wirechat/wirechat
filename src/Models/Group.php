<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Facades\WireChat;

class Group extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'conversation_id',
        'name',
        'description'
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('group');
        parent::__construct($attributes);
    }


    protected static function boot()
    {
        parent::boot();

        // listen to deleted
        static::deleted(function ($group) {

            if ($group->cover?->exists()) {

                //delete cover
                $group->cover?->delete();

                //also delete from storage
                if (file_exists(Storage::disk(WireChat::storageDisk())->exists($group->cover->file_path))) {
                    Storage::disk(WireChat::storageDisk())->delete($group->cover->file_path);
                }
            }

        });
    }

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\GroupFactory::new();
    }


    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    function getCoverUrlAttribute():?string
      {

    return    $this->cover?->url;
        
    }

    public function cover()
    {
        return $this->morphOne(Attachment::class, 'attachable');
    }





}
