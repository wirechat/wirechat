<?php

namespace Namu\WireChat\Enums;

enum ConversationType: string
{
    case SELF = 'self';
    case PRIVATE = 'private';
    case GROUP = 'group';

}
