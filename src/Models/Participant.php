<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Scopes\WithoutRemovedAction;
use Namu\WireChat\Models\Scopes\WithoutRemovedActionScope;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'participantable_id',
        'participantable_type',
        'role',
        'exited_at',
        'conversation_deleted_at',
        'conversation_cleared_at',
        'last_active_at'
    ];


    protected $casts = [
        'role' => ParticipantRole::class,
        'exited_at' => 'datetime',
        'conversation_deleted_at' => 'datetime',
        'conversation_cleared_at'=>'datetime',
        'last_active_at'=>'datetime'
    ];


    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('participants');


        parent::__construct($attributes);
    }


    /**
     * Scope to exclude exited participants by default.
     */
    protected static function booted()
    {
        static::addGlobalScope('withoutExited', function ($query) {
            $query->whereNull('exited_at');
        });

        static::addGlobalScope(WithoutRemovedActionScope::class);


         // listen to deleted
         static::deleted(function ($participant) {

            // Delete reads
            // Use a DB transaction to ensure atomicity
            DB::transaction(function () use ($participant) {
                // Delete associated actions (polymorphic actionable relation)
                $participant->actions()->delete();
            });
        });
    }

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\ParticipantFactory::new();
    }



    /**
     * Polymorphic relation to the participantable model.
     */
    public function participantable()
    {
        return $this->morphTo();
    }



    /**
     * Scope for filtering by participantable model.
     */
    /**
     * Scope for filtering by participantable model.
     */
    public function scopeWhereParticipantable(Builder $query, Model $model): void
    {
        $query->where('participantable_id', $model->id)
            ->where('participantable_type', get_class($model));
    }



    /**
     * Scope for filtering by participantable model.
     */
    /**
     * Remove global scope withoutExited.
     */
    public function scopeWithExited(Builder $query): void
    {
        $query->withoutGlobalScope('withoutExited');
    }



    /**
     * Define a relationship to fetch the conversation.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    /**
     * Check if participant is admin
     **/
    function isAdmin()
    {
        return $this->role == ParticipantRole::OWNER || $this->role == ParticipantRole::ADMIN;
    }

    /**
     * Check if participant is owner of conversation
     **/
    function isOwner()
    {

        return $this->role == ParticipantRole::OWNER;
    }


    /**
     * Mark the participant as exited from the conversation.
     *
     * @param null
     * @return bool
     */
    public function exitConversation(): bool
    {
        #make sure conversation is not private
        abort_if($this->conversation->isPrivate(), 403, "Participant cannot exit a private conversation");

        #make sure owner if group cannot be removed from chat
        abort_if($this->isOwner(), 403, "Owner cannot exit conversation");


        #update Role to Participant
        $this->role= ParticipantRole::PARTICIPANT;
        $this->save();


        if (!$this->hasExited()) {
            $this->exited_at = now();
            return $this->save();
        }


        return false; // Already exited or conversation mismatch
    }

    /**
     * Check if the participant has exited the conversation.
     */
    public function hasExited(): bool
    {
        return $this->exited_at !=null;
    }


      // Relationship with actions table (to track the removal actions)
      public function actions()
      {
          return $this->morphMany(Action::class, 'actionable');
      }



   /**
     * check if participant was removed by admin
     *
     * @return bool
     */
      public function isRemovedByAdmin(): bool
      {
          return $this->actions()
              ->where('type', Actions::REMOVED_BY_ADMIN->value)
              ->exists();
      }


   /**
     * Remove a participant and log the action if not already logged.
     *
     * @param Model $admin The admin model removing the participant.
     * @return void
     */
    function removeByAdmin(Model $admin): void
    {
        // Check if a remove action already exists for this participant
        $exists = Action::where('actionable_id', $this->id)
            ->where('actionable_type', Participant::class)
            ->where('type', Actions::REMOVED_BY_ADMIN)
            ->exists();

        if (!$exists) {
            // Create the 'remove' action record in the actions table
            Action::create([
                'actionable_id' => $this->id,
                'actionable_type' => Participant::class,
                'actor_id' => $admin->id,  // The admin who performed the action
                'actor_type' => get_class($admin),  // Assuming 'User' is the actor model
                'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
            ]);
        }
    }


    /**
     * Determine if the user has deleted this conversation and if the deletion is still "valid."
     *
     * This function checks the `conversation_deleted_at` timestamp to see if the conversation
     * was deleted by the user. Optionally, it can check if this deletion has "expired" by
     * comparing it to the last `updated_at` timestamp of the conversation.
     *
     * - If `$checkDeletionExpired` is true, this method checks if the deletion has expired. A deletion is expired
     *   if the conversation was updated after the user deleted it, meaning new messages or changes were made.
     * - If `$checkDeletionExpired` is false, the method only checks if the conversation is deleted, 
     *   without considering updates.
     *
     * @param bool $checkDeletionExpired Whether to check if the deletion is expired.
     *
     * @return bool True if the conversation is deleted (and expired if `$checkDeletionExpired` is true), false otherwise.
     */
    public function hasDeletedConversation(bool $checkDeletionExpired = false): bool
    {
        // Check if `conversation_deleted_at` is null, which means no deletion
        if ($this->conversation_deleted_at === null) {
            return false;
        }
    
        // Refresh conversation instance to ensure `updated_at` is current
        $conversation = $this->conversation;
    
        if ($checkDeletionExpired) {
            // If checking expiration, return true only if deletion timestamp is older than updated timestamp
            return $this->conversation_deleted_at < $conversation->updated_at;
        }
    
        // Otherwise, return true if deletion is recent compared to updated timestamp
        return true;
    }
    
    // public function ConversationDeletionIsValid(): bool
    // {

    //     return   $this->conversation_deleted_at!=null&&;

    // }
}
