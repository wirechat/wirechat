<?php

namespace Wirechat\Wirechat\Contracts;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Panel;

interface WirechatUser
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
     * Determine if the user can send messages or message requests to the given recipient.
     * Override this to enforce blocking, friend requirements, or any custom rule.
     */
    public function canSendMessageTo(Model $recipient): bool;
}
