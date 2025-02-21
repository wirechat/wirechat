<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Scopes\WithoutRemovedMessages;
use Namu\WireChat\Traits\Actionable;

class Conversation extends Model
{
    use Actionable;
    use HasFactory;

    protected $fillable = [
        'disappearing_started_at',
        'disappearing_duration',
    ];

    protected $casts = [
        'type' => ConversationType::class,
        'updated_at' => 'datetime',
        'disappearing_started_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('conversations');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        // static::addGlobalScope(new WithoutDeletedScope());
        // DELETED event
        static::deleted(function ($conversation) {

            // Use a DB transaction to ensure atomicity
            DB::transaction(function () use ($conversation) {

                // Delete associated participants
                $conversation->participants()->withoutGlobalScopes()->forceDelete();

                // Use a DB transaction to ensure atomicity

                // Delete associated messages
                $conversation->messages()?->withoutGlobalScopes()?->forceDelete();

                // Delete actions
                $conversation->actions()?->delete();

                // Delete group
                $conversation->group()?->delete();
            });
        });

        // static::created(function ($model) {
        //     // Convert the id to base 36 and limit to 6 characters (to leave room for randomness)
        //   //  dd(encrypt($model->id),$model->id);
        //     $baseId = substr(base_convert($model->id, 10, 36), 0, 6); // 6 characters
        //     dd($baseId);
        //     // Generate a random alphanumeric string of 6 characters
        //     $randomString = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6); // 6 characters
        //     // Combine to ensure total length is 12 characters
        //     $model->unique_id = $baseId . $randomString; // Combine them
        //     $model->saveQuietly(); // Save without triggering model events
        // });
        // static::creating(function ($model) {
        //     do {
        //         $uniqueId = Str::random(12);
        //     } while (self::where('unique_id', $uniqueId)->exists());

        //     $model->unique_id = $uniqueId;
        // });
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\ConversationFactory::new();
    }

    /**
     * Define a relationship to fetch participants for this conversation.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class, 'conversation_id', 'id');
    }

    /**
     * Retrieve the participant model for a given user in the conversation.
     *
     * If participants are already loaded, it fetches from the collection.
     * Otherwise, it queries dynamically via the `participants()` relationship.
     *
     * @param  Model|Authenticatable  $user  The user instance.
     * @param  bool  $withoutGlobalScopes  Whether to ignore global scopes in the query.
     * @return Participant|null The corresponding participant or null if not found.
     */
    public function participant(Model|Authenticatable $user, bool $withoutGlobalScopes = false): ?Participant
    {
        $query = $this->relationLoaded('participants')
            ? $this->participants
            : $this->participants();

        if ($withoutGlobalScopes) {
            $query = $query->withoutGlobalScopes();
        }

        return $query->where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->first();
    }

    /**
     * Add a new participant to the conversation.
     *
     * @param Model user the creator of group
     * @param ParticipantRole  a ParticipanRole enum to assign to member
     * @param  bool  $undoAdminRemovalAction  If the user was recently removed by admin, allow re-adding.
     */
    public function addParticipant(Model $user, ParticipantRole $role = ParticipantRole::PARTICIPANT, bool $undoAdminRemovalAction = false): Participant
    {
        // Check if the participant already exists (with or without global scopes)
        $participant = $this->participants()
            ->withoutGlobalScopes()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->first();

        if ($participant) {
            // Abort if the participant exited themselves
            abort_if(
                $participant->hasExited(),
                403,
                __('Cannot add :name because they left the group.', ['name' => $user->display_name])
            );

            // Check if the participant was removed by an admin or owner
            if ($participant->isRemovedByAdmin()) {
                // Abort if undoAdminRemovalAction is not true
                abort_if(
                    ! $undoAdminRemovalAction,
                    403,
                    __('Cannot add :name because they were removed from the group by an Admin.', ['name' => $user->display_name])
                );

                // If undoAdminRemovalAction is true, remove admin removal actions and return the participant
                $participant->actions()
                    ->where('type', Actions::REMOVED_BY_ADMIN)
                    ->delete();

                return $participant;
            }

            // Abort if the participant is already in the group and has not exited
            abort(422, __('Participant is already in the conversation.'));
        }

        // Validate participant limits for private or self conversations
        if ($this->isPrivate()) {
            abort_if(
                $this->participants()->count() >= 2,
                422,
                __('Private conversations cannot have more than two participants.')
            );
        }

        if ($this->isSelf()) {
            abort_if(
                $this->participants()->count() >= 1,
                422,
                __('Self conversations cannot have more than one participant.')
            );
        }

        // Add a new participant
        return $this->participants()->create([
            'participantable_id' => $user->id,
            'participantable_type' => $user->getMorphClass(),
            'role' => $role,
        ]);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    /**
     * ------------------------
     * SCOPES
     */
    public function scopeWhereHasParticipant(Builder $query, $userId, $userType): void
    {
        $query->whereHas('participants', function ($query) use ($userId, $userType) {
            $query->where('participantable_id', $userId)
                ->where('participantable_type', $userType);
        });
    }

    /**
     * Exclude blank conversations that have no messages at all,
     * including those that where deleted by the user- meaning .
     */
    public function scopeWithoutBlanks(Builder $builder): void
    {
        $user = auth()->user(); // Get the authenticated user
        if ($user) {

            $builder->whereHas('messages', function ($q) use ($user) {
                /* !we only exclude one scope not all because we dont want to check aginast soft delete messages */
                $q->withoutGlobalScope(WithoutRemovedMessages::class)->whereDoesntHave('actions', function ($q) use ($user) {
                    $q->whereActor($user)
                        ->where('type', Actions::DELETE);
                });
            });
        }
    }

    /**
     * Scope a query to only include conversation where user cleraed all messsages users.
     */
    public function scopeWithoutCleared(Builder $builder): void
    {
        $user = auth()->user(); // Get the authenticated user

        // dd($model->id);
        // Apply the scope only if the user is authenticated
        if ($user) {

            // Get the table name for conversations dynamically to avoid hardcoding.
            $conversationsTableName = (new Conversation)->getTable();

            // Apply the "without deleted conversations" scope
            $builder->whereHas('participants', function ($query) use ($user, $conversationsTableName) {
                $query->whereParticipantable($user)
                    ->whereRaw(" (conversation_cleared_at IS NULL OR conversation_cleared_at < {$conversationsTableName}.updated_at) ");
            });
        }
    }

    /**
     * Exclude conversations that were marked as deleted by the auth participant
     */
    public function scopeWithoutDeleted(Builder $builder)
    {

        // Dynamically get the parent model (i.e., the user)
        $user = auth()->user();

        if ($user) {
            // Get the table name for conversations dynamically to avoid hardcoding.
            $conversationsTableName = (new Conversation)->getTable();

            // Apply the "without deleted conversations" scope
            $builder->whereHas('participants', function ($query) use ($user, $conversationsTableName) {
                $query->whereParticipantable($user)
                    ->whereRaw(" (conversation_deleted_at IS NULL OR conversation_deleted_at < {$conversationsTableName}.updated_at) ");
            });
        }
    }

    /**
     * Include conversations that were marked as deleted by the auth participant.
     */
    public function scopeWithDeleted(Builder $builder)
    {
        // Dynamically get the parent model (i.e., the user)
        $user = auth()->user();

        if ($user) {
            // Get the table name for conversations dynamically to avoid hardcoding.

            // Apply the "with deleted conversations" scope
            $builder->whereHas('participants', function ($query) use ($user) {
                $query->whereParticipantable($user)
                    ->orWhereNotNull('conversation_deleted_at');
            });
        }
    }

    /**
     * Get the peer participant in a private or self conversation.
     *
     * This method retrieves the other participant in a private conversation
     * or returns the given reference user for self conversations.
     *
     * @param  Model  $reference  The reference user/model to exclude.
     * @return Participant|null The other participant or null if not applicable.
     */
    public function peerParticipant(Model $reference): ?Participant
    {
        // Return null if user does not belong to conversation
        if (! $reference->belongsToConversation($this)) {
            return null;
        }

        if (! in_array($this->type, [ConversationType::PRIVATE, ConversationType::SELF])) {
            return null;
        }

        // Ensure participants is always a collection
        $participants = $this->relationLoaded('participants')
            ? collect($this->participants) // Convert to collection if already loaded
            : $this->participants()->get(); // Fetch as a collection

        if ($this->isSelf()) {
            return $participants->where('participantable_id', $reference->id)->where('participantable_type', $reference->getMorphClass())->first();
        }

        return $participants->reject(fn ($participant) => $participant->participantable_id == $reference->id &&
            $participant->participantable_type == $reference->getMorphClass()
        )->first();
    }

    /**
     * Get all peer participants in a conversation, excluding the reference user.
     *
     * This method retrieves all other participants in a conversation
     * except for the given reference user.
     *
     * @param  Model  $reference  The reference user/model to exclude.
     * @return Collection<int, Participant> A collection of peer participants.
     */
    public function peerParticipants(Model $reference): Collection
    {
        // Return an empty collection if the user does not belong to the conversation
        if (! $reference->belongsToConversation($this)) {
            return collect();
        }

        // Check if 'participants' relationship is already loaded
        if ($this->relationLoaded('participants')) {
            return collect($this->participants)->reject(fn ($participant) => $participant->participantable_id == $reference->id &&
                $participant->participantable_type == $reference->getMorphClass()
            );
        }

        // If not loaded, use the query scope
        return $this->participants()->withoutParticipantable($reference)->get();
    }

    /**
     * Get receiver Participant for Private Conversation
     * will return null for Self Conversation
     */
    public function receiverParticipant(): HasOne
    {
        $user = auth()->user();

        return $this->hasOne(Participant::class)
            ->withoutParticipantable($user)
            ->where('role', ParticipantRole::OWNER)
            ->withWhereHas('conversation', function ($query) {
                $query->whereIn('type', [ConversationType::PRIVATE]);
            });
    }

    /**
     * Get Auth Participant all types Private Conversation
     * will return Auth for Self Conversation
     */
    public function authParticipant(): HasOne
    {
        $user = auth()->user();

        return $this->hasOne(Participant::class)
            ->whereParticipantable($user)
            ->where('role', ParticipantRole::OWNER);
    }

    /**
     * Get the receiver of the private conversation
     *
     * @param null
     * */
    public function getReceiver()
    {
        // Check if the conversation is private or self
        if (! in_array($this->type, [ConversationType::PRIVATE, ConversationType::SELF])) {
            return null;
        }

        // If it's a self conversation, return the authenticated user
        if ($this->isSelf()) {
            return auth()->user();
        }

        // Get participants for the current conversation
        $participants = $this->participants()->where('conversation_id', $this->id);

        // Try to find the receiver excluding the authenticated user
        $receiverParticipant = $participants->withoutParticipantable(auth()->user())->first();
        if ($receiverParticipant) {
            return $receiverParticipant->participantable;
        }

        // If no other participant is found, return the authenticated user as the receiver
        return auth()->user();
    }

    /**
     * Mark the conversation as read for the current authenticated user.
     *
     * @param  Model  $user||null
     *                             If not user is passed ,it will attempt to user auth(),if not avaible then will return null
     */
    public function markAsRead(?Model $user = null)
    {

        $user = $user ?? auth()->user();
        if ($user == null) {

            return null;
            // code...
        }

        $this->participant($user)?->update(['conversation_read_at' => now()]);
    }

    /**
     * Determine if the conversation has been fully read by a specific user.
     *
     * This method checks if the last read timestamp of the participant is later
     * than the last update timestamp of the conversation. If a `Participant`
     * instance is provided, it is used directly; otherwise, the participant
     * is retrieved from the conversation.
     *
     * @param  Model|Participant  $user  The user model or Participant instance.
     * @return bool True if the conversation has been fully read, false otherwise.
     */
    public function readBy(Model|Participant $user): bool
    {
        $participant = $user instanceof Participant ? $user : $this->participant($user);

        return $participant?->conversation_read_at > $this->updated_at;
    }

    /**
     * Retrieve unread messages in this conversation for a specific user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function unreadMessages(Model $user)
    {
        $participant = $this->participant($user);

        if (! $participant) {
            // If the participant is not found, return an empty collection
            return collect();
        }

        $lastReadAt = $participant->conversation_read_at;

        // Check if the messages relation is already loaded
        if ($this->relationLoaded('messages')) {
            // Filter messages based on last read time and exclude messages belonging to the user
            return $this->messages->filter(function ($message) use ($lastReadAt, $user) {
                // If lastReadAt is null, consider all messages as unread
                // Also, exclude messages that belong to the user
                return (! $lastReadAt || $message->created_at > $lastReadAt) && ! $message->ownedBy($user);
            });
        }

        // Query builder for unread messages
        $query = $this->messages();

        // WORKING
        $messages = $query->whereIsNotOwnedBy($user)->when($lastReadAt, function ($query) use ($lastReadAt) {

            $query->where('created_at', '>', $lastReadAt);
        })->get();

        return $messages;
    }

    /**
     * Get unread messages count for the specified user.
     */
    public function getUnreadCountFor(Model $model): int
    {
        // Get unread messages by reusing the unreadMessages method
        $unreadMessages = $this->unreadMessages($model);

        return $unreadMessages->count(); // Return the count of unread messages
    }

    /**
     * ----------------------------------------
     * ----------------------------------------
     * Disappearing
     * --------------------------------------------
     */

    /**
     * Check if conversation allows disappearing messages.
     */
    public function hasDisappearingTurnedOn(): bool
    {
        return ! is_null($this->disappearing_duration) && $this->disappearing_duration > 0 && ! is_null($this->disappearing_started_at);
    }

    /**
     * Turn on disappearing messages for the conversation.
     *
     * @param  int  $durationInSeconds  The duration for disappearing messages in seconds.
     *
     * @throws InvalidArgumentException
     */
    public function turnOnDisappearing(int $durationInSeconds): void
    {
        // Validate that the duration is not negative and is at least 1 hour
        if ($durationInSeconds < 3600) {
            throw new \InvalidArgumentException(__('Disappearing messages duration must be at least 1 hour (3600 seconds).'));
        }

        $this->update([
            'disappearing_duration' => $durationInSeconds,
            'disappearing_started_at' => Carbon::now(),
        ]);
    }

    /**
     * Turn off disappearing messages for the conversation.
     */
    public function turnOffDisappearing(): void
    {
        $this->update([
            'disappearing_duration' => null,
            'disappearing_started_at' => null,
        ]);
    }

    /**
     * Delete all messages for the given participant and check if the conversation can be deleted.
     *
     * @param  Model  $participant  The participant whose messages are to be deleted.
     * @return void|null Returns null if the other participant cannot be found in a private conversation.
     */
    public function deleteFor(Model $user)
    {
        // Ensure the participant belongs to the conversation
        abort_unless($user->belongsToConversation($this), 403, __('User does not belong to conversation'));

        // Clear conversation history for this user
        $this->clearFor($user);

        // Mark this participant's conversation_deleted_at
        $participant = $this->participant($user);
        $participant->conversation_deleted_at = Carbon::now();
        $participant->save();

        // Then force delete it
        if ($this->isSelfConversation($user)) {
            return $this->forceDelete();
        }

        // Check if the conversation is private or self
        if ($this->isPrivate()) {

            // set variable and default value
            $deletedByBothParticipants = true;

            // Get Participants
            // !use make sure to get new query() otherwise participants wont be retrieved correctly
            $participants = $this->participants()->get();

            // Check if all participants have deleted the conversation
            $deletedByBothParticipants = $participants->every(function ($participant) {
                return $participant->hasDeletedConversation(true) == true;
            });

            // If all participants have deleted the conversation, force delete it
            if ($deletedByBothParticipants) {
                $this->forceDelete();
            }
        }
    }

    /**
     * Check if a given user has deleted all messages in the conversation using the deleteForMe
     */
    public function hasBeenDeletedBy(Model $user): bool
    {
        $participant = $this->participant($user);

        return $participant->hasDeletedConversation(checkDeletionExpired: true);
    }

    public function clearFor(Model $user)
    {
        // Ensure the participant belongs to the conversation
        abort_unless($user->belongsToConversation($this), 403, __('User does not belong to conversation'));

        // Update the participant's `conversation_cleared_at` to the current timestamp
        $this->participant($user)->update(['conversation_cleared_at' => now()]);
    }

    /**
     * Check if the conversation is a self conversations
     */
    public function isSelfConversation(?Model $participant = null): bool
    {

        return $this->isSelf();
    }

    /**
     * ------------------------------------------
     *  ROOM CONFIGURATION
     *
     * -------------------------------------------
     */
    public function group()
    {
        return $this->hasOne(Group::class, 'conversation_id');
    }

    public function isPrivate(): bool
    {
        return $this->type == ConversationType::PRIVATE;
    }

    public function isSelf(): bool
    {
        return $this->type == ConversationType::SELF;
    }

    public function isGroup(): bool
    {
        return $this->type == ConversationType::GROUP;
    }

    /**
     * ------------------------------------------
     *  Role Checks
     * -------------------------------------------
     */
    public function isOwner(Model|Authenticatable $model): bool
    {

        $pariticipant = $this->participant($model);

        return $pariticipant->isOwner();
    }

    /**
     * ------------------------------------------
     *  Role Checks
     * -------------------------------------------
     */
    public function isAdmin(Model|Authenticatable $model): bool
    {

        $pariticipant = $this->participant($model);

        return $pariticipant->isAdmin();
    }
}
