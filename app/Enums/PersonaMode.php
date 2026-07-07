<?php

namespace App\Enums;

enum PersonaMode: string
{
    case CHOOSE_PERSONA = 'CHOOSE_PERSONA';
    case RANDOM_PERSONA = 'RANDOM_PERSONA';
    case HIDDEN_PERSONA = 'HIDDEN_PERSONA';
}
