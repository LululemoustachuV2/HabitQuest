<?php

namespace App\Service\Quest\Evaluator;

use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\QuestConditionKind;
use App\Service\StreakService;

final class StreakDaysEvaluator implements ConditionEvaluatorInterface
{
    public function __construct(
        private readonly StreakService $streakService,
    ) {
    }

    public function supports(QuestConditionKind $kind): bool
    {
        return $kind === QuestConditionKind::STREAK_DAYS;
    }

    public function getTarget(array $params): int
    {
        return (int) ($params['days'] ?? 0);
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
        return 0;
    }

    public function recomputeCurrent(array $params, User $user, \DateTimeImmutable $since): ?int
    {
        unset($since);

        if (array_key_exists('habitId', $params)) {
            return $this->streakService->getHabitStreakDays($user, (int) $params['habitId']);
        }

        return $user->getCurrentStreak();
    }
}

