<?php

namespace Wirechat\Wirechat\Enums;

enum MessageRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DISMISSED = 'dismissed';
}
