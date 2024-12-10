<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Scopes\WithoutDeletedScope;

class Conversation extends Model
{
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
        //DELETED event
        static::deleted(function ($conversation) {

            // Use a DB transaction to ensure atomicity
            DB::transaction(function () use ($conversation) {

                // Delete associated participants
                $conversation->participants()->withoutGlobalScopes()->forceDelete();

                // Use a DB transaction to ensure atomicity

                // Delete associated messages
                $conversation->messages()->withoutGlobalScopes()->forceDelete();

                //Delete actions
                $conversation->actions()->delete();

                //Delete group
                $conversation->group()->delete();
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
     * Get a participant model  from the user
     *
     * @return Participant|null
     */
    public function participant(Model|Authenticatable $user, bool $withoutGlobalScopes = false)
    {
        $query = Participant::where('participantable_id', $user->id)
            ->where('participantable_type', get_class($user))
            ->where('conversation_id', $this->id); // Ensures you're querying for the correct conversation

        if ($withoutGlobalScopes) {
            $query->withoutGlobalScopes(); // Apply this condition if necessary
        }

        // Retrieve the participant directly
        $participant = $query->first();

        return $participant;
    }

    // public function participant(Model $user)
    // {

    //     $participant = null;
    //     // If loaded, simply check the existing collection
    //     if ($this->relationLoaded('participants')) {
    //         $participant = $this->participants()
    //             ->withoutGlobalScope('withoutExited')
    //             ->where('participantable_id', $user->id)
    //             ->where('participantable_type', get_class($user))
    //             ->first();
    //     } else {
    //         $participant = $this->participants()
    //             ->withoutGlobalScope('withoutExited')

    //             ->where('participantable_id', $user->id)
    //             ->where('participantable_type', get_class($user))
    //             ->first();
    //     }

    //     return $participant;
    // }

    /**
     * Add a new participant to the conversation.
     *
     * @param  bool  $revive  =if user was recently deleted by admin or owner then add them back
     */
    // public function addParticipant(Model $user, bool $revive = false): Participant
    // {
    //     // Check if the participant is already in the conversation
    //     abort_if(
    //         $this->participants()
    //             ->where('participantable_id', $user->id)
    //             ->where('participantable_type', get_class($user))
    //             ->exists(),
    //         422,
    //         'Participant is already in the conversation.'
    //     );

    //     #If the conversation is private, ensure it doesn't exceed two participants
    //     if ($this->isPrivate()) {
    //         abort_if(
    //             $this->participants()->count() >= 2,
    //             422,
    //             'Private conversations cannot have more than two participants.'
    //         );
    //     }

    //     #ensure Self conversations do not have more than 1 participant
    //     if ($this->isSelf()) {
    //         abort_if(
    //             $this->participants()->count() >= 1,
    //             422,
    //             'Self conversations cannot have more than 1 participant.'
    //         );
    //     }

    //     $participantWithoutScopes = $this->participants()
    //         ->withoutGlobalScopes()
    //         ->where('participantable_id', $user->id)
    //         ->where('participantable_type', get_class($user))
    //         ->first();

    //     if ($participantWithoutScopes) {
    //         # abort if exited already exited group
    //         abort_if($participantWithoutScopes?->hasExited(), 403, 'Cannot add ' . $user->display_name . ' because they left the group');

    //         #reomve removed_by_action if existed
    //         if ($revive) {
    //             $participantWithoutScopes->actions()->where('type', Actions::REMOVED_BY_ADMIN)->delete();
    //         }

    //         return $participantWithoutScopes;
    //     } else {

    //         #create particicipant
    //         $participant = $this->participants()->withoutGlobalScopes()->updateOrCreate([
    //             'participantable_id' => $user->id,
    //             'participantable_type' => get_class($user),
    //             'role' => ParticipantRole::PARTICIPANT
    //         ]);

    //         return $participant;
    //     }
    // }

    /**
     * Add a new participant to the conversation.
     *
     * @param ParticipantRole  a ParticipanRole enum to assign to member
     * @param  bool  $undoAdminRemovalAction  If the user was recently removed by admin, allow re-adding.
     */
    public function addParticipant(Model $user, ParticipantRole $role = ParticipantRole::PARTICIPANT, bool $undoAdminRemovalAction = false): Participant
    {
        // Check if the participant already exists (with or without global scopes)
        $participant = $this->participants()
            ->withoutGlobalScopes()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', get_class($user))
            ->first();

        if ($participant) {
            // Abort if the participant exited themselves
            abort_if(
                $participant->hasExited(),
                403,
                'Cannot add '.$user->display_name.' because they left the group.'
            );

            // Check if the participant was removed by an admin or owner
            if ($participant->isRemovedByAdmin()) {
                // Abort if undoAdminRemovalAction is not true
                abort_if(
                    ! $undoAdminRemovalAction,
                    403,
                    'Cannot add '.$user->display_name.' because they were removed from the group by an Admin.'
                );

                // If undoAdminRemovalAction is true, remove admin removal actions and return the participant
                $participant->actions()
                    ->where('type', Actions::REMOVED_BY_ADMIN)
                    ->delete();

                return $participant;
            }

            // Abort if the participant is already in the group and has not exited
            abort(422, 'Participant is already in the conversation.');
        }

        // Validate participant limits for private or self conversations
        if ($this->isPrivate()) {
            abort_if(
                $this->participants()->count() >= 2,
                422,
                'Private conversations cannot have more than two participants.'
            );
        }

        if ($this->isSelf()) {
            abort_if(
                $this->participants()->count() >= 1,
                422,
                'Self conversations cannot have more than one participant.'
            );
        }

        // Add a new participant
        return $this->participants()->create([
            'participantable_id' => $user->id,
            'participantable_type' => get_class($user),
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
     * including those that where deleted by the user.
     */
    public function scopeWithoutBlanks(Builder $builder): void
    {
        $user = auth()->user(); // Get the authenticated user
        if ($user) {

            $builder->whereHas('messages', function ($q) use ($user) {
                $q->withoutGlobalScopes()->whereDoesntHave('actions', function ($q) use ($user) {
                    $q->where('actor_id', '!=', $user->id)
                        ->where('actor_type', get_class($user)) // Safe since $user is authenticated
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
                $query->where('participantable_id', $user->id)
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
                $query->where('participantable_id', $user->id)
                    ->whereRaw(" (conversation_deleted_at IS NULL OR conversation_deleted_at < {$conversationsTableName}.updated_at) ");
            });
        }
    }

    // public function scopeWithDeleted(Builder $builder)
    // {

    //     // Dynamically get the parent model (i.e., the user)
    //     $user = auth()->user();

    //     if ($user) {
    //         // Get the table name for conversations dynamically to avoid hardcoding.
    //         $conversationsTableName = (new Conversation())->getTable();

    //         // Apply the "without deleted conversations" scope
    //         $builder->whereHas('participants', function ($query) use ($user, $conversationsTableName) {
    //             $query->where('participantable_id', $user->id)
    //                 ->whereRaw("
    //                     (conversation_deleted_at IS NULL OR conversation_deleted_at < {$conversationsTableName}.updated_at)
    //                 ");
    //         });
    //     }
    // }

    public function receiver(): HasOne
    {
        return $this->hasOne(Participant::class)
            ->where('participantable_id', '!=', auth()->id())
            ->where('role', 'owner')->whereHas('conversation', function ($query) {
                $query->whereIn('type', [ConversationType::PRIVATE, ConversationType::SELF]);
            });
    }

    /**
     * Get the receiver of the private conversation
     *
     * */
    public function getReceiver()
    {

        // Check if the conversation is private
        //  dd($this->type);
        if (! in_array($this->type, [ConversationType::PRIVATE, ConversationType::SELF])) {
            return null;
        }

        // Ensure participants are already loaded (use the loaded relationship, not fresh queries)
        $participants = $this->participants->where('conversation_id', $this->id);

        $receiverParticipant = $participants->where('participantable_id', '!=', auth()->id())
            ->where('participantable_type', get_class(auth()->user()))
            ->first();

        if ($receiverParticipant) {
            // Return the associated model via the participant's relationship

            //  dd('reacch',$receiverParticipant->participantable);
            return $receiverParticipant->participantable;
        }

        // Check the number of times the user appears as participant (fallback case)
        $authReceiver = $participants->where('participantable_id', auth()->id())
            ->where('participantable_type', get_class(auth()->user()))
            ->first();

        return $authReceiver?->participantable;
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
     * Check if the conversation has been fully read by a specific user.
     * This returns true if there are no unread messages after the conversation
     * was marked as read by the user.
     */
    public function readBy(Model $user): bool
    {
        // Reuse the unread count method and return true if unread count is 0
        return $this->getUnreadCountFor($user) <= 0;
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
                return (! $lastReadAt || $message->created_at > $lastReadAt) &&
                    $message->sendable_id != $user->id && $message->sendable_type == get_class($user);
            });
        }

        // Query builder for unread messages
        $query = $this->messages();
        if ($lastReadAt) {
            $query->where('created_at', '>', $lastReadAt);
        }

        // Exclude messages that belong to the user
        return $query->where('sendable_id', '!=', $user->id)
            ->where('sendable_type', get_class($user))
            ->get(); // Return the collection of unread messages
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
            throw new \InvalidArgumentException('Disappearing messages duration must be at least 1 hour (3600 seconds).');
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
     * Delete all messages for the given participant and check if the conversation can be deleted.
     *
     * @param  Model  $participant  The participant whose messages are to be deleted.
     * @return void|null Returns null if the other participant cannot be found in a private conversation.
     */
    public function deleteFor(Model $user)
    {
        // Ensure the participant belongs to the conversation
        abort_unless($user->belongsToConversation($this), 403, 'User does not belong to conversation');

        //Clear conversation history for this user
        $this->clearFor($user);

        //Mark this participant's conversation_deleted_at
        $participant = $this->participant($user);
        $participant->conversation_deleted_at = Carbon::now();
        $participant->save();

        // Check if the conversation is private or self
        if ($this->isPrivate() || $this->isSelf()) {
            //check if conversatin is Self conversation
            //Then force delete it
            if ($this->isSelfConversation($user)) {
                $this->forceDelete();
            } else {

                // Retrieve the other participant in the private conversation
                $otherParticipant = $this->participants
                    ->where('participantable_id', '!=', $user->id)
                    ->where('participantable_type', get_class($user))
                    ->first();

                // Return null if the other participant cannot be found
                if (! $otherParticipant) {
                    return null;
                }

                // If both participants have deleted all their messages, delete the conversation permanently
                if ($participant->hasDeletedConversation() && $otherParticipant->hasDeletedConversation()) {
                    // dd("deleted");
                    $this->forceDelete();
                }
            }
        }
    }

    /**
     * Check if a given user has deleted all messages in the conversation using the deleteForMe
     */
    public function hasBeenDeletedBy(Model $user): bool
    {
        $participant = $this->participant($user);

        return $participant->hasDeletedConversation(true);
    }

    public function clearFor(Model $user)
    {
        // Ensure the participant belongs to the conversation
        abort_unless($user->belongsToConversation($this), 403, 'User does not belong to conversation');

        // Update the participant's `conversation_cleared_at` to the current timestamp
        $this->participant($user)->update(['conversation_cleared_at' => now()]);
    }

    /**
     * Check if the conversation is owned by the  user themselves
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
