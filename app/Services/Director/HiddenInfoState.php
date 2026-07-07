<?php

namespace App\Services\Director;

enum HiddenInfoState: string
{
    case LOCKED = 'LOCKED';
    case ELIGIBLE = 'ELIGIBLE';
    case DISCLOSED_PARTIAL = 'DISCLOSED_PARTIAL';
    case DISCLOSED_FULL = 'DISCLOSED_FULL';
}
