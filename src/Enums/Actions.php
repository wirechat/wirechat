<?php

namespace Namu\WireChat\Enums;

enum Actions: string
{
    case DELETE = 'delete';
    case ARCHIVE = 'archive';
    case REMOVED_BY_ADMIN = 'removed-by-admin';

}
