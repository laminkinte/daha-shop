<?php

namespace App\Enums;

enum AgentAvailability: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Offline = 'offline';
}
