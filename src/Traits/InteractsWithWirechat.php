<?php

namespace Wirechat\Wirechat\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Events\MessageRequestUpdated;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;

/**
 * @property-read string|null $cover_url
 * @property-read string|null $display_name
 * @property-read string|null $profile_url
 * @property-read string|null $wirechat_avatar_url
 * @property-read string|null $wirechat_name
 * @property-read string|null $wirechat_profile_url
 *
 * @method string displayName()
 */
trait InteractsWithWirechat
{
    use InteractsWithPanel;

    /**
     * Establishes a relationship between the user and conversations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<Conversation, static>
     */
    public function conversations()
    {
        return $this->morphToMany(
            Wirechat::conversationModelClass(), // The related model
            'participantable',   // The polymorphic field (participantable_id & participantable_type)
            Wirechat::participantModelTable(), // The participants table
            'participantable_id', // The foreign key on the participants table for the User model
            'conversation_id'     // The foreign key for the Conversation model
        )->withPivot('conversation_id'); // Optionally load conversation_id from the pivot table
    }

    /**
     * @return MorphOne<\Wirechat\Wirechat\Models\Setting, static>
     */
    public function wirechatSettings(): MorphOne
    {
        return $this->morphOne(Wirechat::settingModelClass(), 'owner');
    }

    /**
     * Creates a private conversation with another participant and adds participants.
     *
     * @param  Model  $participant  The participant to create a conversation with
     * @param  string|null  $message  The initial message (optional)
     */
    public function createConversationWith(Model $peer, ?string $message = null): ?Conversation
    {
        abort_unless($this->canCreateChats(), 403, 'You do not have permission to create chats.');
        abort_unless($this->canSendMessageTo($peer), 403, 'You are not allowed to send messages to this user.');

        $authType = $this->getMorphClass();
        $authId = (string) $this->getKey();

        $peerType = $peer->getMorphClass();
        $peerId = (string) $peer->getKey();

        $isSelf = ($authId === $peerId) && ($authType === $peerType);
        $type = $isSelf ? ConversationType::SELF : ConversationType::PRIVATE;

        $existing = $isSelf
            ? Wirechat::conversationModelClass()::withoutGlobalScopes()
                ->where('type', $type)
                ->whereHas('participants', function ($q) use ($authType, $authId) {
                    $q->where('participantable_type', $authType)
                        ->where('participantable_id', $authId);
                })
                ->first()
            : $this->findExistingPrivateConversationForPair(
                authType: $authType,
                authId: $authId,
                peerType: $peerType,
                peerId: $peerId,
            );

        if ($existing) {
            if (! $isSelf) {
                $incomingRequest = $existing->pendingMessageRequestFor($this);
                $sender = $incomingRequest?->sender;

                if ($incomingRequest) {
                    $existing->acceptMessageRequestFor($this, $this);
                    $existing->touch();
                    $this->dispatchMessageRequestRealtimeUpdate($sender, $existing, MessageRequestStatus::ACCEPTED);

                    return $existing->fresh();
                }
            }

            return $existing;
        }

        return DB::transaction(function () use ($type, $isSelf, $authType, $authId, $peerType, $peerId, $message) {
            $conversation = Wirechat::conversationModel();
            $conversation->type = $type;
            $conversation->save();

            // ensure/insert participants (idempotent)
            $authParticipant = Wirechat::participantModelClass()::firstOrCreate(
                [
                    'conversation_id' => $conversation->getKey(),
                    'participantable_type' => $authType,
                    'participantable_id' => $authId,
                ],
                ['role' => ParticipantRole::OWNER]
            );

            if (! $isSelf) {
                Wirechat::participantModelClass()::firstOrCreate(
                    [
                        'conversation_id' => $conversation->getKey(),
                        'participantable_type' => $peerType,
                        'participantable_id' => $peerId,
                    ],
                    ['role' => ParticipantRole::OWNER]
                );
            }

            if (! empty($message)) {
                Wirechat::messageModelClass()::create([
                    'participant_id' => $authParticipant->getKey(),   // sender = auth participant
                    'conversation_id' => $conversation->getKey(),      // keep denormalized for speed
                    'body' => $message,
                ]);
            }

            return $conversation;
        });
    }

    /**
     * Creates or reuses a private conversation that begins as a message request.
     *
     * When the opposite-direction request already exists and is still pending,
     * that request is accepted immediately so only one active request can exist
     * for the pair at a time.
     */
    public function sendMessageRequestTo(Model $peer): ?Conversation
    {
        abort_unless($this->canCreateChats(), 403, 'You do not have permission to create chats.');
        abort_unless($this->canSendMessageTo($peer), 403, 'You are not allowed to send messages to this user.');

        $authType = $this->getMorphClass();
        $authId = (string) $this->getKey();

        $peerType = $peer->getMorphClass();
        $peerId = (string) $peer->getKey();

        $isSelf = ($authId === $peerId) && ($authType === $peerType);

        if ($isSelf) {
            return $this->createConversationWith($peer);
        }

        $existingConversation = $this->findExistingPrivateConversationForPair(
            authType: $authType,
            authId: $authId,
            peerType: $peerType,
            peerId: $peerId,
        );

        if ($existingConversation) {
            $incomingRequest = $existingConversation->pendingMessageRequestFor($this);
            $sender = $incomingRequest?->sender;

            if ($incomingRequest) {
                $existingConversation->acceptMessageRequestFor($this, $this);
                $existingConversation->touch();
                $this->dispatchMessageRequestRealtimeUpdate($sender, $existingConversation, MessageRequestStatus::ACCEPTED);

                return $existingConversation->fresh();
            }

            return $existingConversation;
        }

        $conversation = DB::transaction(function () use ($authType, $authId, $peer) {
            $conversation = Wirechat::conversationModel();
            $conversation->type = ConversationType::PRIVATE;
            $conversation->save();

            Wirechat::participantModelClass()::firstOrCreate(
                [
                    'conversation_id' => $conversation->getKey(),
                    'participantable_type' => $authType,
                    'participantable_id' => $authId,
                ],
                ['role' => ParticipantRole::OWNER]
            );

            $conversation->createMessageRequestFor($peer, $this);

            return $conversation;
        });

        $this->dispatchMessageRequestRealtimeUpdate($peer, $conversation, MessageRequestStatus::PENDING);

        return $conversation;
    }

    protected function findExistingPrivateConversationForPair(
        string $authType,
        string $authId,
        string $peerType,
        string $peerId
    ): ?Conversation {
        return Wirechat::conversationModelClass()::withoutGlobalScopes()
            ->where('type', ConversationType::PRIVATE)
            ->where(function ($query) use ($authType, $authId, $peerType, $peerId) {
                $query
                    ->where(function ($acceptedConversation) use ($authType, $authId, $peerType, $peerId) {
                        $acceptedConversation
                            ->whereHas('participants', function ($participantQuery) use ($authType, $authId) {
                                $participantQuery->where('participantable_type', $authType)
                                    ->where('participantable_id', $authId);
                            })
                            ->whereHas('participants', function ($participantQuery) use ($peerType, $peerId) {
                                $participantQuery->where('participantable_type', $peerType)
                                    ->where('participantable_id', $peerId);
                            });
                    })
                    ->orWhereHas('messageRequests', function ($requestQuery) use ($authType, $authId, $peerType, $peerId) {
                        $requestQuery
                            ->pending()
                            ->where(function ($pairQuery) use ($authType, $authId, $peerType, $peerId) {
                                $pairQuery
                                    ->where(function ($forward) use ($authType, $authId, $peerType, $peerId) {
                                        $forward->where('sender_type', $authType)
                                            ->where('sender_id', $authId)
                                            ->where('recipient_type', $peerType)
                                            ->where('recipient_id', $peerId);
                                    })
                                    ->orWhere(function ($reverse) use ($authType, $authId, $peerType, $peerId) {
                                        $reverse->where('sender_type', $peerType)
                                            ->where('sender_id', $peerId)
                                            ->where('recipient_type', $authType)
                                            ->where('recipient_id', $authId);
                                    });
                            });
                    });
            })
            ->latest('updated_at')
            ->first();
    }

    protected function dispatchMessageRequestRealtimeUpdate(
        ?Model $participantable,
        Conversation $conversation,
        MessageRequestStatus $status
    ): void {
        if (! $participantable) {
            return;
        }

        $panelId = Wirechat::currentPanel()?->getId();

        if (! $panelId) {
            try {
                $panelId = Wirechat::getDefaultPanel()?->getId();
            } catch (\Throwable) {
                return;
            }
        }

        event(new MessageRequestUpdated($participantable, $conversation->getKey(), $status, $panelId));
    }

    /**
     * Room configuration
     */

    /**
     * Create group
     */
    public function createGroup(string $name, ?string $description = null, ?UploadedFile $photo = null, Panel|string|null $panel = null): Conversation
    {

        // abort if is not allowed to create new groups
        abort_unless($this->canCreateGroups(), 403, 'You do not have permission to create groups.');

        // Otherwise, create a new conversation
        $conversation = Wirechat::conversationModel();
        $conversation->type = ConversationType::GROUP;
        $conversation->save();

        // create room
        $group = $conversation->group()->create([
            'name' => $name,
            'description' => $description,
        ]);

        // create and save photo is present
        if ($photo) {
            // save photo to disk
            $path = $photo->store(Wirechat::storage()->attachmentsDirectory(), Wirechat::storage()->disk());

            // create attachment
            $group->cover()->create([
                'file_path' => $path,
                'file_name' => basename($path),
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => Attachment::resolveMimeType(
                    $photo,
                    $path,
                    Wirechat::storage()->disk()
                ),
                'url' => Storage::disk(Wirechat::storage()->disk())->url($path),
            ]);
        }

        // create participant as owner
        Wirechat::participantModelClass()::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $this->id,
            'participantable_type' => $this->getMorphClass(),
            'role' => ParticipantRole::OWNER,
        ]);

        return $conversation;
    }

    /**
     * Exit a chat:group|channel by marking the user's participant record as exited.
     */
    public function exitConversation(Conversation $conversation): bool
    {

        // get participant
        $participant = $conversation->participant($this);

        return $participant ? $participant->exitConversation() : false;
    }

    /**
     * Creates a conversation if one doesn't already exist with the recipient model,
     * or uses an existing conversation directly, and sends the attached message.
     * Works with both private and group conversations in a polymorphic manner.
     *
     * @param  Model  $model  - The recipient model or conversation instance
     * @param  string  $message  - The message content to send
     * @return Message|null
     */
    public function sendMessageTo(Model $model, string $message)
    {
        // Check if the recipient is a model (polymorphic) and not a conversation
        if (! $model instanceof Conversation) {
            // Ensure the model has the required trait
            if (
                ! in_array(InteractsWithWirechat::class, class_uses($model)) &&
                ! in_array(Chatable::class, class_uses($model))
            ) {
                abort(403, 'The model must use `InteractsWithWirechat` trait and must implement WirechatUser');
            }

            // Deprecation notice if Chatable is still in use
            if (in_array(Chatable::class, class_uses($model))) {
                trigger_error(
                    'The `Chatable` trait is deprecated. Please use `InteractsWithWirechat` instead.',
                    E_USER_DEPRECATED
                );
            }

            // Create or get a private conversation with the recipient
            $conversation = $this->createConversationWith($model);
        } else {
            // If it's a Conversation, use it directly
            $conversation = $model;

            // Optionally, check that the current model is part of the conversation
            if (! $this->belongsToConversation($conversation)) {
                abort(403, 'You do not have access to this conversation.'); // Exit if not a participant
            }
        }

        // Proceed to create the message if a valid conversation is found or created
        if ($conversation) {
            // get auth participant
            $participant = $conversation->participant($this);

            $createdMessage = Wirechat::messageModelClass()::create([
                'conversation_id' => $conversation->id,
                'participant_id' => $participant->getKey(),
                'body' => $message,
            ]);

            // update auth participant last active
            $participant->update(['last_active_at' => now()]);

            // Update the conversation timestamp
            $conversation->updated_at = now();
            $conversation->save();

            return $createdMessage;
        }

        return null;
    }

    /**
     * Determine if this user can send a message or message request to the given recipient.
     * Returns true by default. Override in your User model to enforce blocking,
     * friendship requirements, or any other custom rule.
     *
     * Example:
     *   public function canSendMessageTo(Model $recipient): bool
     *   {
     *       return ! $recipient->hasBlocked($this) && ! $this->hasBlocked($recipient);
     *   }
     */
    public function canSendMessageTo(Model $recipient): bool
    {
        return true;
    }

    public function canAccessConversation(Conversation $conversation): bool
    {
        $panel = app(PanelRegistry::class)->getCurrent();

        if ($panel && ! $panel->hasMessageRequests() && ! $this->belongsToConversation($conversation)) {
            return false;
        }

        return $conversation->canBeAccessedBy($this);
    }

    /**
     * Accessor returns the URL for the user's cover image (used as an avatar).
     * Customize this based on your avatar field.
     *
     * @deprecated since 0.3.0 — use {@see getWirechatAvatarUrlAttribute()} instead.
     */
    public function getCoverUrlAttribute(): ?string
    {
        return null;  // Adjust 'avatar_url' to your field
    }

    /**
     * Accessor returns the URL for the user's profile page.
     * Customize this based on your routing or profile setup.
     *
     * @deprecated since 0.3.0 — use {@see getWirechatProfileUrlAttribute()} instead.
     */
    public function getProfileUrlAttribute(): ?string
    {
        return null;  // Adjust 'profile' route as needed
    }

    /**
     * Accessor returns the display name for the user.
     * Customize this based on your display name field.
     *
     * @deprecated since 0.3.0 — use {@see getWirechatNameAttribute()} instead.
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->name ?? 'user';  // Adjust 'name' field if needed
    }

    /**
     * Get Wirechat name
     */
    public function getWirechatNameAttribute(): ?string
    {
        // fallback to old `display_name` for backward compatibility
        return $this->wirechat_name ?? ($this->display_name);
    }

    /**
     * Get Wirechat avatar url
     */
    public function getWirechatAvatarUrlAttribute(): ?string
    {
        return $this->wirechat_avatar_url ?? $this->cover_url;
    }

    /**
     * Get Wirechat Profile Url
     * Customize this based on your routing or profile setup.
     */
    public function getWirechatProfileUrlAttribute(): ?string
    {
        return $this->wirechat_profile_url ?? $this->profile_url;
    }

    /**
     * Get unread messages count for the user, across all conversations or within a specific conversation.
     */
    public function getUnreadCount(?Conversation $conversation = null): int
    {
        // If a specific conversation is provided, use the conversation's getUnreadCountFor method
        if ($conversation) {
            return $conversation->getUnreadCountFor($this);
        }

        $conversationModelClass = Wirechat::conversationModelClass();

        return $conversationModelClass::getTotalUnreadCountFor($this);
    }

    /**
     * Define the relationship to the conversation.
     */
    public function belongsToConversation(Conversation $conversation, bool $withoutGlobalScopes = false): bool
    {
        // Check if participants are already loaded
        if ($conversation->relationLoaded('participants')) {
            // If loaded, simply check the existing collection
            $participants = $conversation->participants;

            if ($withoutGlobalScopes) {
                $participants->withoutGlobalScopes();
            }

            return $participants->contains(function ($participant) {
                return $participant->participantable_id == $this->getKey() &&
                    $participant->participantable_type == $this->getMorphClass();
            });
        }

        $participants = $conversation->participants();

        if ($withoutGlobalScopes) {
            $participants->withoutGlobalScopes();
        }

        // If not loaded, perform the query
        return $participants
            ->where('participantable_id', $this->getKey())
            ->where('participantable_type', $this->getMorphClass())
            ->exists();
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(Conversation $conversation): void
    {

        // use already created methods inside conversation model
        $conversation->deleteFor($this);
    }

    /**
     * Clear a conversation
     */
    public function clearConversation(Conversation $conversation)
    {

        // use already created methods inside conversation model
        $conversation->clearFor($this);
    }

    /**
     * Check if the user has a private conversation with another user.
     */
    public function hasConversationWith(Model $user): bool
    {

        $participantId = $user->getKey();
        $participantType = $user->getMorphClass();

        $authenticatedUserId = $this->id;
        $authenticatedUserType = $this->getMorphClass();

        // Check if this is a self-conversation (both participants are the authenticated user)
        $selfConversationCheck = $participantId === $authenticatedUserId && $participantType === $authenticatedUserType;

        // Define the base query for finding conversations
        $existingConversationQuery = Wirechat::conversationModelClass()::whereIn('type', [ConversationType::PRIVATE, ConversationType::SELF]);

        // If it's a self-conversation, adjust the query to check for two identical participants
        if ($selfConversationCheck) {
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType) {
                $query->select('conversation_id')
                    ->where('participantable_id', $authenticatedUserId)
                    ->where('participantable_type', $authenticatedUserType)
                    ->whereType(ConversationType::SELF)
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(*) = 1'); // Ensuring two participants in the conversation
            });
        } else {
            // If it's a conversation between two different participants, adjust the query accordingly
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType, $participantId, $participantType) {
                $query->select('conversation_id')
                    ->whereIn('participantable_id', [$authenticatedUserId, $participantId])
                    ->whereIn('participantable_type', [$authenticatedUserType, $participantType])
                    ->whereType(ConversationType::PRIVATE)
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(*) = 2'); // Ensure both participants are different
            });
        }

        // Execute the query and get the first matching conversation
        return $existingConversationQuery->exists();
    }

    /**
     * Check if the user has deleted a conversation.
     *
     * @param  Conversation  $conversation  The conversation to check for deletion status.
     * @param  bool  $checkDeletionExpired  Optional. When true, checks if the deletion has "expired."
     *                                      Deletion is considered expired if the conversation has been updated after it was deleted by the user.
     *                                      Default is false, which checks only if the conversation has been deleted, regardless of updates.
     * @return bool True if the conversation is deleted, false otherwise.
     */
    public function hasDeletedConversation(Conversation $conversation, bool $checkDeletionExpired = false): bool
    {
        $participant = $conversation->participant($this);

        return $participant?->hasDeletedConversation($checkDeletionExpired);
    }

    public function conversationDeletionExpired(Conversation $conversation): bool
    {

        return $this->hasDeletedConversation($conversation, true);
    }

    /* Checking roles in conversation */

    /**
     * Check if the user is an admin in a specific conversation.
     * Or if is owner , because owner can also be admin
     */
    public function isAdminIn(Group|Conversation $entity): bool
    {

        // check if is not Conversation model
        if (! ($entity instanceof Conversation)) {

            $conversation = $entity->conversation;
        }
        // means it is group to get Parent Relationship
        else {

            $conversation = $entity;
        }

        $pariticipant = $conversation->participant($this);

        return $pariticipant->isAdmin() || $pariticipant->isOwner();
    }

    /**
     * Check if the user is the owner of a specific conversation.
     */
    public function isOwnerOf(Group|Conversation $entity): bool
    {

        // check if is not Conversation model
        if (! ($entity instanceof Conversation)) {

            $conversation = $entity->conversation;
        }
        // means it is grouped to get Parent Relationship
        else {

            $conversation = $entity;
        }
        // If not loaded, perform the query
        $pariticipant = $conversation->participant($this);

        return (bool) $pariticipant?->isOwner();
    }
}
