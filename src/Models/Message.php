<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Namu\WireChat\Enums\Actions;

class Message extends Model
{
    use HasFactory;



    protected $fillable = [
        'body',
        'sendable_type', // Now includes sendable_type for polymorphism
        'sendable_id',   // Now includes sendable_id for polymorphis
        'conversation_id',
        'attachment_id',
        'reply_id'
    ];


    //  protected $dates=['read_at','receiver_deleted_at','sender_deleted_at'];
    protected $userModel;

    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.messages_table');


        $this->userModel = app(config('wirechat.user_model', \App\Models\User::class));

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


    protected static function boot()
    {
        parent::boot();

        //Add scope if authenticated
        static::addGlobalScope('excludeDeleted', function (Builder $builder) {
        if (auth()->check()) {

            $builder->whereDoesntHave('actions', function ($q) {
                $q->where('actor_id', auth()->id())
                    ->where('actor_type', get_class(auth()->user()))
                    ->where('type', Actions::DELETE);
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
                // Delete associated reads (polymorphic readable relation)
                $message->reads()->delete();
            });


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
        return $this->hasMany(Read::class, 'message_id');
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
     * Delete for me 
     * This will delete the message only for the auth user meaning other participants will be able to see it
     */
    public function deleteForMe()
    {
        abort_unless(auth()->check(), 401);

        //make sure auth belongs to conversation for this message
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        // Try to create an action
        $this->actions()->create([
            'actor_id' => auth()->id(),
            'actor_type' => get_class(auth()->user()),
            'type' => Actions::DELETE,
        ]);
    }
}
