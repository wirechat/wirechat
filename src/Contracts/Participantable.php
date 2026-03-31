<?php

namespace Wirechat\Wirechat\Contracts;

use Illuminate\Database\Eloquent\Model;
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
     * Get conversations relationship.
     */
    public function conversations();

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
}
