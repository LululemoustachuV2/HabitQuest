<?php

namespace App\Enum;

enum UserQuestStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
}
