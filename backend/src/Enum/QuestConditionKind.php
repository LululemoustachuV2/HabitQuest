<?php

namespace App\Enum;

enum QuestConditionKind: string
{
    case HABIT_LOGS_COUNT = 'habit_logs_count';
    case CATEGORY_LOGS_COUNT = 'category_logs_count';
    case XP_GAINED = 'xp_gained';
    case GOLD_GAINED = 'gold_gained';
    case STREAK_DAYS = 'streak_days';
    case QUESTS_VALIDATED_COUNT = 'quests_validated_count';

    public static function values(): array
    {
        return array_map(static fn (self $k): string => $k->value, self::cases());
    }
}

