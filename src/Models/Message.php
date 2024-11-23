<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Facades\WireChat;

class Message extends Model
{
    use HasFactory; use SoftDeletes;



    protected $fillable = [
        'body',
        'sendable_type', // Now includes sendable_type for polymorphism
        'sendable_id',   // Now includes sendable_id for polymorphis
        'conversation_id',
        'reply_id',
        'type'
    ];


    protected $casts=[
        'type'=>MessageType::class
    ];


    //  protected $dates=['read_at','receiver_deleted_at','sender_deleted_at'];
    public function __construct(array $attributes = [])
    {
        $this->table =WireChat::formatTableName('messages');

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

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\MessageFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

      // Add scope if authenticated
        static::addGlobalScope('excludeDeleted', function (Builder $builder) {

            $messagesTableName = (new  Message() )->getTable();
            $participantTableName = (new Participant())->getTable();

            if (auth()->check()) {
                $user = auth()->user();

                $builder->whereDoesntHave('actions', function ($q) use ($user) {
                    $q->where('actor_id', $user->id)
                    ->where('actor_type', get_class($user))
                    ->where('type', Actions::DELETE);
                })
                ->where(function ($query) use ($user,$messagesTableName,$participantTableName) {
                    // Filter messages based on `conversation_deleted_at` in the participants table
                    $query->whereHas('conversation.participants', function ($q) use ($user,$messagesTableName,$participantTableName) {
                        $q->where('participantable_id', $user->id)
                        ->where('participantable_type', get_class($user))
                        ->where(function ($q)  use ($messagesTableName,$participantTableName) {
                            $q->whereNull('conversation_cleared_at') // Include all messages if not cleared
                                ->orWhereColumn("$messagesTableName.created_at", '>', "$participantTableName.conversation_cleared_at");
                        });
                    });
                });
            }
        });

        // listen to deleted
        static::deleted(function ($message) {

            if ($message->attachment?->exists()) {

                //delete attachment
                $message->attachment?->delete();

                //also delete from storage
                if (file_exists(Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->exists($message->attachment->file_path))) {
                    Storage::disk(config('wirechat.attachments.storage_disk', 'public'))->delete($message->attachment->file_path);
                }
            }


            // Delete reads
            // Use a DB transaction to ensure atomicity
            DB::transaction(function () use ($message) {
                // Delete associated actions (polymorphic actionable relation)
                $message->actions()->delete();
            });
        });
    }

    public function attachment()
    {
        return $this->morphOne(Attachment::class, 'attachable');
    }


    public function hasAttachment()
    {
        return $this->attachment()->exists();
    }

  
    /**
     * Check if the message has been read by a specific user.
     *
     * @param Model $user
     * @return bool
     */
    public function readBy(Model $user): bool
    {
        // Check if the reads relationship is loaded

        $read = $user->reads()->where('conversation_id',$this->conversation_id)->first();


        return $read?->read_at >$this->created_at;

    
    }


     /**
     * Check if the message is owned by user
     *
     * @return bool
     */
    public function ownedBy($user): bool
    {
        if (!$user || !($user instanceof \Illuminate\Database\Eloquent\Model)) {
            return false;
        }

    
        return $this->sendable_type == get_class($user) && $this->sendable_id == $user->id;
    }
    



    public function belongsToAuth(): bool
    {
        $user = auth()->user();
        return $this->sendable_type == get_class($user) && $this->sendable_id == $user->id;
    }


    // Relationship for the parent message
    public function parent()
    {
        return $this->belongsTo(Message::class, 'reply_id')->withoutGlobalScope('excludeDeleted')->withTrashed();
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
     * ----------------------------------------
     * ----------------------------------------
     * Actions 
     * A message can have many actions by different users)
     * --------------------------------------------
     */

    public function actions()
    {
        return $this->morphMany(Action::class, 'actionable', 'actionable_type', 'actionable_id', 'id');
    }

    /**
     * Delete for 
     * This will delete the message only for the auth user meaning other participants will be able to see it
     */
    public function deleteFor($user)
    {
        if (!$user || !($user instanceof \Illuminate\Database\Eloquent\Model)) {
            return false;
        }
    

        //make sure auth belongs to conversation for this message
        abort_unless($user->belongsToConversation($this->conversation), 403);

        // Try to create an action
        $this->actions()->create([
            'actor_id' => $user->id,
            'actor_type' => get_class($user),
            'type' => Actions::DELETE,
        ]);
    }
    
}
