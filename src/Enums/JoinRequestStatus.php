<?php

namespace Wirechat\Wirechat\Enums;

enum JoinRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DISMISSED = 'dismissed';
}
