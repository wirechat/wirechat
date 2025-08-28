<?php

namespace Namu\WireChat\Contracts;

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
}
