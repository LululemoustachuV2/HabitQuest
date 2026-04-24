<?php

namespace App\Enum;

enum QuestKind: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case PROGRESSION = 'progression';
    case EVENT = 'event';
}
