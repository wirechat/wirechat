<?php

namespace Namu\WireChat\Contracts;

use Namu\WireChat\Panel;

interface WireChatUser
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
    public function canAccessWireChatPanel(Panel $panel): bool;
}
