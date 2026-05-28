<?php

namespace App\Enum;

enum AchievementCode: string
{
    case FIRST_QUEST_VALIDATED = 'first_quest_validated';
    case FIRST_MONSTER_KILL = 'first_monster_kill';
    case IRON_DISCIPLINE = 'iron_discipline';

    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}

