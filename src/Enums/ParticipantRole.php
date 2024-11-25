<?php

namespace Namu\WireChat\Enums;

enum ParticipantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case PARTICIPANT = 'participant';

}
