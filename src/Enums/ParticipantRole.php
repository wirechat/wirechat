<?php

namespace Wirechat\Wirechat\Enums;

enum ParticipantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case PARTICIPANT = 'participant';

}
