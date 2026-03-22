<?php

namespace Wirechat\Wirechat\Enums;

enum Actions: string
{
    case DELETE = 'delete';
    case ARCHIVE = 'archive';
    case REMOVED_BY_ADMIN = 'removed-by-admin';
    case BLOCKED_BY_ADMIN = 'blocked-by-admin';
    case JOIN_REQUEST = 'join-request';

}
