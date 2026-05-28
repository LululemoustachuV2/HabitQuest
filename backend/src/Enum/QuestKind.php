<?php

namespace App\Enum;

enum QuestKind: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case PROGRESSION = 'progression';
    case EVENT = 'event';

    public function defaultBaseDamage(): int
    {
        return match ($this) {
            self::DAILY => 15,
            self::WEEKLY => 35,
            self::PROGRESSION => 25,
            self::EVENT => 20,
        };
    }
}

