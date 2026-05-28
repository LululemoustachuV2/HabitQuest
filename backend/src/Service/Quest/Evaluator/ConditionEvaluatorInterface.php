<?php

namespace App\Service\Quest\Evaluator;

use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\QuestConditionKind;

interface ConditionEvaluatorInterface
{
    public function supports(QuestConditionKind $kind): bool;

    public function getTarget(array $params): int;

    public function appliesToLog(array $params, HabitLog $habitLog): bool;

    public function incrementForLog(array $params, HabitLog $habitLog): int;

    public function recomputeCurrent(array $params, User $user, \DateTimeImmutable $since): ?int;
}

