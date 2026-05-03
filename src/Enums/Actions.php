<?php

namespace Wirechat\Wirechat\Enums;

enum Actions: string
{
    case DELETE = 'delete';
    case ARCHIVE = 'archive';
    case REMOVED_BY_ADMIN = 'removed-by-admin';
    case BLOCKED_BY_ADMIN = 'blocked-by-admin';
    case BANNED_BY_ADMIN = 'banned-by-admin';
    case JOIN_REQUEST = 'join-request';

    /**
     * Backward-compatible action values that represent an admin ban.
     *
     * @return array<int, string>
     */
    public static function bannedValues(): array
    {
        return [
            self::BANNED_BY_ADMIN->value,
            self::BLOCKED_BY_ADMIN->value,
        ];
    }
}
