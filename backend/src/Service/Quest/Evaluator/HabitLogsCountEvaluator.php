<?php

namespace App\Service\Quest\Evaluator;

use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\QuestConditionKind;

final class HabitLogsCountEvaluator implements ConditionEvaluatorInterface
{
    public function supports(QuestConditionKind $kind): bool
    {
        return $kind === QuestConditionKind::HABIT_LOGS_COUNT;
    }

    public function getTarget(array $params): int
    {
        return (int) ($params['count'] ?? 0);
    }

    public function appliesToLog(array $params, HabitLog $habitLog): bool
    {
        if (!array_key_exists('habitId', $params)) {
            return true;
        }

        return $habitLog->getHabit()->getId() === (int) $params['habitId'];
    }

    public function incrementForLog(array $params, HabitLog $habitLog): int
    {
        return 1;
    }

    public function recomputeCurrent(array $params, User $user, \DateTimeImmutable $since): ?int
    {
        return null;
    }
}

