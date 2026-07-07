<?php

namespace App\Services\Director;

enum ConversationPhase: string
{
    case OPENING = 'OPENING';
    case RAPPORT = 'RAPPORT';
    case DISCOVERY = 'DISCOVERY';
    case NEED_EXPLORATION = 'NEED_EXPLORATION';
    case EXPLANATION = 'EXPLANATION';
    case OBJECTION_HANDLING = 'OBJECTION_HANDLING';
    case COMMITMENT = 'COMMITMENT';
    case CLOSING = 'CLOSING';
    case ENDING = 'ENDING';
}
