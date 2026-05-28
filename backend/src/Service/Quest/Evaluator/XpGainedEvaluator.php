<?php

namespace App\Service\Quest\Evaluator;

use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\QuestConditionKind;

final class XpGainedEvaluator implements ConditionEvaluatorInterface
{
    public function supports(QuestConditionKind $kind): bool
    {
        return $kind === QuestConditionKind::XP_GAINED;
    }

    public function getTarget(array $params): int
    {
        return (int) ($params['amount'] ?? 0);
    }

    public function appliesToLog(array $params, HabitLog $habitLog): bool
    {
        return $habitLog->getXpEarned() > 0;
    }

    public function incrementForLog(array $params, HabitLog $habitLog): int
    {
        return $habitLog->getXpEarned();
    }

    public function recomputeCurrent(array $params, User $user, \DateTimeImmutable $since): ?int
    {
        return null;
    }
}

