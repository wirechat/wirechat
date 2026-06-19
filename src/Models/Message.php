<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\Helper;
use Wirechat\Wirechat\Models\Scopes\WithoutRemovedMessages;
use Wirechat\Wirechat\Traits\Actionable;
use Wirechat\Wirechat\Workbench\Database\Factories\MessageFactory;

/**
 * @property int $id
 * @property int|null $conversation_id
 * @property int $participant_id
 * @property int|null $reply_id
 * @property string|null $body
 * @property MessageType $type
 * @property Carbon|null $kept_at filled when a message is kept from disappearing
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Action> $actions
 * @property-read int|null $actions_count
 * @property-read Attachment|null $attachment
 * @property-read Conversation|null $conversation
 * @property-read Message|null $parent
 * @property-read Message|null $reply
 * @property-read Model|\Eloquent $sendable
 * @property-read Participant|null $participant
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereIsNotOwnedBy(\Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereKeptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereReplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Message withoutTrashed()
 *
 * @mixin \Eloquent
 */
// TODO:update all references of sendable_id and type tp particiapnt id
class Message extends Model
{
    use Actionable;
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'body',
        'participant_id',
        'reply_id',
        'conversation_id',
        'type',
        'kept_at',
    ];

    protected $casts = [
        'type' => MessageType::class,
        'kept_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('messages');

        parent::__construct($attributes);
    }

    /* relationship */

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Wirechat::conversationModelClass());
    }

    /**
     * @deprecated Use $message->user instead via the participant relationship.
     * @see Message::getUserAttribute()
     * Previously, messages had a polymorphic `sendable` relationship (sendable_type/sendable_id),
     * but now messages are linked to a participant, which provides the actual user.
     * So both $message->sendable and $message->user return the participantable.
     */
    public function getSendableAttribute()
    {
        return $this->getUserAttribute();
    }

    /**
     * Relationship to the Participant model.
     *
     * Each message belongs to a participant. This allows you to access
     * the participant who sent the message via `$message->participant`.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Wirechat::participantModelClass(), 'participant_id');
    }

    /**
     * Accessor to get the actual user (participantable) who sent the message.
     *
     * Since participants are polymorphic (`participantable`), this returns
     * the underlying user model (e.g., `User`) associated with the participant.
     * You can access it via `$message->user`.
     */
    public function getUserAttribute()
    {
        return $this->participant?->participantable;
    }

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return MessageFactory::new();
    }

    protected static function booted()
    {
        // Add scope if authenticated
        static::addGlobalScope(WithoutRemovedMessages::class);

        static::saving(function (Message $message) {
            if ($message->type === MessageType::ATTACHMENT) {
                return;
            }

            $body = trim((string) $message->body);

            if ($body === '') {
                $message->type = MessageType::TEXT;

                return;
            }

            $message->type = Wirechat::containsLink($body)
                ? MessageType::LINK
                : MessageType::TEXT;
        });

        // listen to deleted
        static::deleted(function ($message) {

            if ($message->attachment?->exists()) {

                // delete attachment
                $message->attachment->delete();

            }

            // Use a DB transaction to ensure atomicity
            DB::transaction(function () use ($message) {
                // Delete associated actions (polymorphic actionable relation)
                $message->actions()->delete();
            });
        });
    }

    public function attachment(): MorphOne
    {
        return $this->morphOne(Wirechat::attachmentModelClass(), 'attachable');
    }

    public function hasAttachment(): bool
    {
        return $this->attachment()->exists();
    }

    public function isAttachment(): bool
    {
        return $this->type === MessageType::ATTACHMENT;
    }

    public function isLink(): bool
    {
        return $this->type === MessageType::LINK;
    }

    /**
     * Check if the message has been read by a specific user.
     */
    public function readBy(Model|Participant $user): bool
    {
        if ($user instanceof Participant) {
            $user = $user->participantable;
        }

        return $this->conversation->getUnreadCountFor($user) <= 0;
    }

    /**
     * Check if the message is owned by user
     */
    public function ownedBy($user): bool
    {
        if (! $user || ! ($user instanceof Model)) {
            return false;
        }

        if (! $this->participant) {
            return false;
        }

        return $this->participant->participantable_type === $user->getMorphClass()
            && $this->participant->participantable_id == $user->getKey();
    }

    public function belongsToAuth(): bool
    {
        $user = auth()->user();

        if (! $user || ! $this->participant) {
            return false;
        }

        return $this->participant->participantable_type === $user->getMorphClass()
            && $this->participant->participantable_id == $user->getKey();
    }

    // Relationship for the parent message
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Wirechat::messageModelClass(), 'reply_id')->withoutGlobalScope(WithoutRemovedMessages::class)->withTrashed();
    }

    // Relationship for the reply
    public function reply(): HasOne
    {
        return $this->hasOne(Wirechat::messageModelClass(), 'reply_id');
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

    public function scopeWhereIsNotOwnedBy($query, Model|Authenticatable $user)
    {
        return $query->whereDoesntHave('participant', function ($q) use ($user) {
            $q->where('participantable_type', $user->getMorphClass())
                ->where('participantable_id', $user->getKey());
        });
    }

    /**
     * Delete for
     * This will delete the message only for the auth user meaning other participants will be able to see it
     *
     * @return bool|null
     */
    public function deleteFor(Model|Authenticatable $user)
    {
        $conversation = $this->conversation;

        // Make sure auth belongs to conversation for this message
        abort_unless($user->belongsToConversation($conversation), 403);

        // If conversation is self, then delete permanently directly
        if ($conversation->isSelf()) {
            return $this->forceDelete();
        }

        // Resolve this user's participant in the conversation
        $actorParticipant = $conversation->participant($user);

        abort_unless($actorParticipant != null, 403, 'You do not belong to this conversation');

        // Create an action with PARTICIPANT as actor
        $this->actions()->create([
            'actor_id' => $actorParticipant->getKey(),       // unsignedBigInteger
            'actor_type' => $actorParticipant->getMorphClass(), // respects morph map
            'type' => Actions::DELETE,
        ]);

        // If it's a private conversation (only 2 participants), check if both deleted the message
        if ($conversation->isPrivate()) {
            $conversation->loadMissing('participants');

            $deletedByBothParticipants = true;

            foreach ($conversation->participants as $participant) {
                $deletedByBothParticipants = $deletedByBothParticipants &&
                    $this->actions()
                        ->where('actor_id', $participant->getKey())
                        ->where('actor_type', $participant->getMorphClass())
                        ->where('type', Actions::DELETE)
                        ->exists();
            }

            if ($deletedByBothParticipants) {
                return $this->forceDelete();
            }
        }

        return null;
    }

    /**
     * Deleting message for everyone   */
    public function deleteForEveryone(Model $user): void
    {

        $conversation = $this->conversation;
        $participant = $conversation->participant($user);
        $message = $this;

        // Make sure auth belongs to conversation for this message
        abort_unless($user->belongsToConversation($conversation), 403, 'You do not belong to this conversation');

        // make sure user owns message OR allow if is admin in group
        abort_unless($message->ownedBy($user) || ($participant->isAdmin() && $message->conversation->isGroup()), 403, 'You do not have permission to delete this message');

        // if message has reply then only-soft delete it
        if ($message->hasReply()) {
            $message->delete();
        } else {

            $message->forceDelete();
        }

    }

    /**
     * Check if the message body contains only emojis.
     */
    public function isEmoji(): bool
    {
        if ($this->body == null) {
            return false;
        }

        // Use the isEmoji helper method to check if the message body contains only emojis
        return Helper::isEmoji($this->body);
    }
}
