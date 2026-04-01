<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Wirechat\Wirechat\Contracts\Participantable;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Scopes\WithoutRemovedActionScope;
use Wirechat\Wirechat\Traits\Actionable;
use Wirechat\Wirechat\Traits\Actor;

/**
 * @property int $id
 * @property int $conversation_id
 * @property ParticipantRole $role
 * @property int $participantable_id
 * @property string $participantable_type
 * @property \Illuminate\Support\Carbon|null $exited_at
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property \Illuminate\Support\Carbon|null $conversation_cleared_at
 * @property \Illuminate\Support\Carbon|null $conversation_deleted_at
 * @property \Illuminate\Support\Carbon|null $conversation_read_at
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Wirechat\Wirechat\Models\Action> $actions
 * @property-read int|null $actions_count
 * @property-read \Wirechat\Wirechat\Models\Conversation $conversation
 * @property-read Model|\Eloquent $participantable
 *
 * @method static Builder|Participant newModelQuery()
 * @method static Builder|Participant newQuery()
 * @method static Builder|Participant query()
 * @method static Builder|Participant whereConversationClearedAt($value)
 * @method static Builder|Participant whereConversationDeletedAt($value)
 * @method static Builder|Participant whereConversationId($value)
 * @method static Builder|Participant whereConversationReadAt($value)
 * @method static Builder|Participant whereCreatedAt($value)
 * @method static Builder|Participant whereDeletedAt($value)
 * @method static Builder|Participant whereExitedAt($value)
 * @method static Builder|Participant whereId($value)
 * @method static Builder|Participant whereLastActiveAt($value)
 * @method static Builder|Participant whereParticipantable(\Illuminate\Database\Eloquent\Model $model)
 * @method static Builder|Participant whereParticipantableId($value)
 * @method static Builder|Participant whereParticipantableType($value)
 * @method static Builder|Participant whereRole($value)
 * @method static Builder|Participant whereUpdatedAt($value)
 * @method static Builder|Participant withExited()
 * @method static Builder|Participant withoutParticipantable(\Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @mixin \Eloquent
 */
class Participant extends Model
{
    use Actionable;
    use Actor;
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
        $this->table = Wirechat::formatTableName('participants');

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
     *
     * @return \Wirechat\Wirechat\Workbench\Database\Factories\ParticipantFactory
     */
    protected static function newFactory()
    {
        return \Wirechat\Wirechat\Workbench\Database\Factories\ParticipantFactory::new();
    }

    /**
     * Polymorphic relation to the participantable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model,covariant $this>
     */
    public function participantable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Wirechat::messageModelClass(), 'participant_id');
    }

    /** Optional: fastest fetch of the latest message */
    public function latestMessage()
    {
        return $this->hasOne(Wirechat::messageModelClass(), 'participant_id')->latestOfMany();
    }

    /**
     * Scope for filtering by participantable model.
     *
     * @param  Builder<Model>  $query
     */
    public function scopeWhereParticipantable(Builder $query, Participantable $model): void
    {
        $query->where('participantable_id', $model->getKey())
            ->where('participantable_type', $model->getMorphClass());
    }

    /**
     * Remove the "withoutExited" global scope to include exited participants.
     *
     * @param  Builder<\Wirechat\Wirechat\Models\Participant>  $query
     */
    public function scopeWithExited(Builder $query): void
    {
        $query->withoutGlobalScope('withoutExited');
    }

    /**
     * Get the conversation this participant belongs to.
     *
     * @return BelongsTo<Conversation, covariant $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Wirechat::conversationModelClass());
    }

    /**
     * Check if participant is admin
     **/
    public function isAdmin(): bool
    {
        return $this->role === ParticipantRole::OWNER || $this->role === ParticipantRole::ADMIN;
    }

    /**
     * Check if participant is owner of conversation
     **/
    public function isOwner(): bool
    {

        return $this->role === ParticipantRole::OWNER;
    }

    /**
     * Mark the participant as exited from the conversation.
     */
    public function exitConversation(): bool
    {
        // make sure conversation is not private
        abort_if($this->conversation->isPrivate(), 403, 'Participant cannot exit a private conversation');

        // make sure owner if group cannot be removed from chat
        abort_if($this->isOwner(), 403, 'Owner cannot exit conversation');

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
     */
    public function removeByAdmin(Participantable $admin): void
    {
        // Resolve admin as participant in this conversation
        $adminParticipant = $this->conversation->participant($admin);

        if (! $adminParticipant) {
            // admin does not belong to this conversation as a participant
            return;
        }

        // Check if a remove action already exists for this participant
        $exists = Wirechat::actionModelClass()::where('actionable_id', $this->getKey())
            ->where('actionable_type', $this->getMorphClass())  // 🔁 match create()
            ->where('type', Actions::REMOVED_BY_ADMIN)
            ->where('actor_id', $adminParticipant->getKey())
            ->where('actor_type', $adminParticipant->getMorphClass())
            ->exists();

        if (! $exists) {
            Wirechat::actionModelClass()::create([
                'actionable_id' => $this->getKey(),
                'actionable_type' => $this->getMorphClass(),          // participant model
                'actor_id' => $adminParticipant->getKey(),     // admin as participant
                'actor_type' => $adminParticipant->getMorphClass(),
                'type' => Actions::REMOVED_BY_ADMIN,
            ]);
        }

        // downgrade role to normal participant
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
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWithoutParticipantable($query, Participantable $user): Builder
    {

        return $query->where(function ($query) use ($user) {
            $query->where('participantable_id', '<>', $user->getKey())
                ->orWhere('participantable_type', '<>', $user->getMorphClass());
        });
    }
}
