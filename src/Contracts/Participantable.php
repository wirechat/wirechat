<?php

namespace Wirechat\Wirechat\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Panel;

interface Participantable
{
    /**
     * Determine if the user can create new groups.
     */
    public function canCreateGroups(): bool;

    /**
     * Determine if the user can create new chats with other users.
     */
    public function canCreateChats(): bool;

    /**
     * Determine if the user can access wirechat panel.
     */
    public function canAccessWirechatPanel(Panel $panel): bool;

    /**
     * Get the unique identifier for the model.
     */
    public function getKey();

    /**
     * Get the class name for polymorphic relations.
     */
    public function getMorphClass();

    /**
     * Get conversations relationship.
     */
    public function conversations(): MorphToMany;

    /**
     * Create a conversation with another participant.
     */
    public function createConversationWith(Model $peer, ?string $message = null): ?Conversation;

    /**
     * Create a group.
     */
    public function createGroup(string $name, ?string $description = null, ?\Illuminate\Http\UploadedFile $photo = null, Panel|string|null $panel = null): Conversation;

    /**
     * Check if belongs to a conversation.
     */
    public function belongsToConversation(Conversation $conversation, bool $withoutGlobalScopes = false): bool;

    /**
     * Exit a conversation.
     */
    public function exitConversation(Conversation $conversation): bool;

    /**
     * Check if is admin in a conversation.
     */
    public function isAdminIn(Group|Conversation $entity): bool;

    /**
     * Check if is owner of a conversation.
     */
    public function isOwnerOf(Group|Conversation $entity): bool;

    /**
     * Delete a conversation for the participant.
     */
    public function deleteConversation(Conversation $conversation): void;

    /**
     * Clear a conversation for the participant.
     */
    public function clearConversation(Conversation $conversation): void;

    /**
     * Send a message to a model or conversation.
     */
    public function sendMessageTo(Model $model, string $message);

    /**
     * Get the wirechat display name attribute.
     */
    public function getWirechatNameAttribute(): ?string;

    /**
     * Get the wirechat avatar URL attribute.
     */
    public function getWirechatAvatarUrlAttribute(): ?string;

    /**
     * Get the wirechat profile URL attribute.
     */
    public function getWirechatProfileUrlAttribute(): ?string;
}
