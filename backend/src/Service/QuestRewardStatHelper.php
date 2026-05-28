<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\StatType;

final class QuestRewardStatHelper
{
    public static function parseStatBonuses(?array $params): array
    {
        if ($params === null || !isset($params['stats']) || !is_array($params['stats'])) {
            return [];
        }

        $bonuses = [];
        foreach ($params['stats'] as $statKey => $points) {
            if (!is_string($statKey) || !in_array($statKey, StatType::values(), true)) {
                continue;
            }
            if (!is_int($points) && !is_float($points) && !is_string($points)) {
                continue;
            }
            $normalized = (int) $points;
            if ($normalized <= 0) {
                continue;
            }
            $bonuses[$statKey] = $normalized;
        }

        return $bonuses;
    }

    public static function formatForApi(?array $params): array
    {
        $formatted = [];
        foreach (self::parseStatBonuses($params) as $stat => $points) {
            $formatted[] = ['stat' => $stat, 'points' => $points];
        }

        return $formatted;
    }

    public static function applyStatBonuses(User $user, ?array $params, StatService $statService): void
    {
        foreach (self::parseStatBonuses($params) as $statKey => $points) {
            $statService->addStatPoints(
                $user,
                StatType::from($statKey),
                $points,
                StatService::SOURCE_QUEST_REWARD
            );
        }
    }
}

