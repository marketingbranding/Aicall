<?php

namespace App\Services\Director;

enum ObjectionState: string
{
    case DORMANT = 'DORMANT';
    case ACTIVE_HIDDEN = 'ACTIVE_HIDDEN';
    case ACTIVE_VISIBLE = 'ACTIVE_VISIBLE';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case PARTIALLY_RESOLVED = 'PARTIALLY_RESOLVED';
    case RESOLVED = 'RESOLVED';
    case REACTIVATED = 'REACTIVATED';
}
