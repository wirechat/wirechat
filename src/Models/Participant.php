<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Scopes\WithoutRemovedActionScope;
use Namu\WireChat\Traits\Actionable;

class Participant extends Model
{
    use Actionable;
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'participantable_id',
        'participantable_type',
        'role',
        'exited_at',
        'conversation_deleted_at',
        'conversation_cleared_at',
        'conversation_read_at',
        'last_active_at',
    ];

    protected $casts = [
        'role' => ParticipantRole::class,
        'exited_at' => 'datetime',
        'conversation_deleted_at' => 'datetime',
        'conversation_cleared_at' => 'datetime',
        'conversation_read_at' => 'datetime',
        'last_active_at' => 'datetime',
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
            ->where('participantable_type', $model->getMorphClass());
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
    public function isAdmin(): bool
    {
        return $this->role == ParticipantRole::OWNER || $this->role == ParticipantRole::ADMIN;
    }

    /**
     * Check if participant is owner of conversation
     **/
    public function isOwner(): bool
    {

        return $this->role == ParticipantRole::OWNER;
    }

    /**
     * Mark the participant as exited from the conversation.
     *
     * @param null
     */
    public function exitConversation(): bool
    {
        // make sure conversation is not private
        abort_if($this->conversation->isPrivate(), 403, __('Participant cannot exit a private conversation'));

        // make sure owner if group cannot be removed from chat
        abort_if($this->isOwner(), 403, __('Owner cannot exit conversation'));

        // update Role to Participant
        $this->role = ParticipantRole::PARTICIPANT;
        $this->save();

        if (! $this->hasExited()) {
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
        return $this->exited_at != null;
    }

    /**
     * check if participant was removed by admin
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
     * @param  Model  $admin  The admin model removing the participant.
     */
    public function removeByAdmin(Model $admin): void
    {
        // Check if a remove action already exists for this participant
        $exists = Action::where('actionable_id', $this->id)
            ->where('actionable_type', Participant::class)
            ->where('type', Actions::REMOVED_BY_ADMIN)
            ->exists();

        if (! $exists) {
            // Create the 'remove' action record in the actions table
            Action::create([
                'actionable_id' => $this->id,
                'actionable_type' => Participant::class,
                'actor_id' => $admin->id,  // The admin who performed the action
                'actor_type' => $admin->getMorphClass(),  // Assuming 'User' is the actor model
                'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
            ]);
        }
        // update Role to Participant
        $this->role = ParticipantRole::PARTICIPANT;
        $this->save();
    }

    /**
     * Check if the user has deleted the conversation and if the deletion is still valid.
     *
     * This method checks if the user has marked the conversation as deleted by looking at the `conversation_deleted_at` timestamp.
     * Optionally, it can check if the deletion is still valid by comparing the deletion time with the last update time of the conversation.
     *
     * - If `$checkDeletionExpired` is true, the method checks if the deletion is still valid. A deletion is considered expired
     *   if the conversation has been updated after the user deleted it (e.g., new messages).
     * - If `$checkDeletionExpired` is false, it only checks if the conversation has been deleted, regardless of updates.
     *
     * @param  bool  $checkDeletionExpired  Whether to check if the deletion is still valid.
     * @return bool True if the conversation is deleted (and valid if `$checkDeletionExpired` is true), false otherwise.
     */
    public function hasDeletedConversation(bool $checkDeletionExpired = false): bool
    {
        // If no deletion timestamp is set, the conversation isn't deleted
        if ($this->conversation_deleted_at === null) {
            return false;
        }

        // loadMissing Conversation
        $this->loadMissing('conversation');

        // Get the latest updated_at timestamp for the conversation
        $conversation = $this->conversation;

        // Expited conversation means hasDeletedConversation should return FALSE
        if ($checkDeletionExpired) {
            // Check if the deletion timestamp is older than the last update timestamp (i.e., check if deletion is expired)
            return $conversation->updated_at > $this->conversation_deleted_at ? false : true;
        }

        // If not checking expiration, simply return true if the conversation is marked as deleted
        return true;
    }

    /**
     * Exclude participant passed as parameter
     */
    public function scopeWithoutParticipantable($query, Model $user): Builder
    {

        return $query->where(function ($query) use ($user) {
            $query->where('participantable_id', '<>', $user->id)
                ->orWhere('participantable_type', '<>', $user->getMorphClass());
        });

        //  return $query->where(function ($query) use ($user) {
        //      $query->whereNot('participantable_id', $user->id)
        //            ->orWhereNot('participantable_type', $user->getMorphClass());
        //  });
    }
}
