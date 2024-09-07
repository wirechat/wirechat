<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    

    protected $fillable=[
        'body',
        'sendable_type', // Now includes sendable_type for polymorphism
        'sendable_id',   // Now includes sendable_id for polymorphis
        'conversation_id',
        'read_at',
        'receiver_deleted_at',
        'sender_deleted_at',
        'attachment_id',
        'reply_id'
    ];


    protected $dates=['read_at','receiver_deleted_at','sender_deleted_at'];
    protected $userModel;

    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.messages_table');

       
        $this->userModel =app(config('wirechat.user_model',\App\Models\User::class));

        parent::__construct($attributes);
    }

     /* relationship */

     public function conversation()
     {
         return $this->belongsTo(Conversation::class);
     }


    /* Polymorphic relationship for the sender */
    public function sendable()
    {
        return $this->morphTo();
    }
    
 
     public function user()
     {
         return $this->belongsTo($this->userModel::class, 'user_id');
     }
         /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\MessageFactory::new();
    }


    protected static function booted()
    {

        //listen to 
        static::deleted(function ($message) {
        if($message->attachment?->exists()){

           //delete attachment
           $message->attachment?->delete();

           //todo:also delete from storage
           if(file_exists(Storage::disk(config('wirechat.attachments.storage_disk','public'))->exists($message->attachment->file_path)))
            {
                // 1. possibility
                Storage::disk(config('wirechat.attachments.storage_disk','public'))->delete($message->attachment->file_path);
            }
        }

         // Delete reads
         // Use a DB transaction to ensure atomicity
         DB::transaction(function () use ($message) {
            // Delete associated reads (polymorphic readable relation)
            $message->reads()->delete();
         });

        });

    }

  

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }



    public function hasAttachment()
    {
        return $this->attachment()->exists();
    }

 
    /**
     * Get all of the reads for the message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reads(): HasMany
    {
        return $this->hasMany(Read::class,'message_id');
    }

    public function markAsRead()
    {

        $authUser = auth()->user();
        // Create a read record if it doesn't already exist
        $this->reads()->firstOrCreate([
                'readable_id' => $authUser->id,
                'readable_type' => get_class($authUser),
            ], [
                'read_at' => now(),
            ]);

    }
    /**
     * Check if the message has been read by a specific user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function readBy($user): bool
    {
        return $this->reads()
            ->where('readable_id', $user->id)
            ->where('readable_type', get_class($user))
            ->exists();
    }

  
    public function belongsToAuth(): bool
    {
        $user = auth()->user();
        return $this->sendable_type === get_class($user) && $this->sendable_id == $user->id;
    }


    // Relationship for the parent message
    public function parent()
    {
        return $this->belongsTo(Message::class, 'reply_id');
    }

    // Relationship for the reply
    public function reply()
    {
        return $this->hasOne(Message::class, 'reply_id');
    }

    // Method to check if the message has a reply
    public function hasReply(): bool
    {
        return $this->reply()->exists();
    }

      // Method to check if the message has a parent
      public function hasParent(): bool
      {
          return $this->parent()->exists();
      }


    /**
     * ------------------
     *
     * @param $query
     * -------------
     */
    // public function scopeWhereNotDeleted($query)
    // {
    //     $userId = auth()->id();

    //     return $query->where(function ($subQuery) use ($userId) {
    //         $subQuery->where('sender_id', $userId)->whereNull('sender_deleted_at');
    //     })
    //         ->orWhere(function ($subQuery) use ($userId) {
    //             $subQuery->where('receiver_id', $userId)->whereNull('receiver_deleted_at');
    //         });
    // }

}
