<?php

namespace Wirechat\Wirechat\Enums;

enum ConversationType: string
{
    case SELF = 'self';
    case PRIVATE = 'private';
    case GROUP = 'group';

}
