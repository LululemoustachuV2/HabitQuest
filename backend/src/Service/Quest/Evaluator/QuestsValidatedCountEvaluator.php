<?php

namespace App\Service\Quest\Evaluator;

use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\QuestConditionKind;
use App\Repository\UserQuestActionLogRepository;

final class QuestsValidatedCountEvaluator implements ConditionEvaluatorInterface
{
    public function __construct(
        private readonly UserQuestActionLogRepository $actionLogRepository,
    ) {
    }

    public function supports(QuestConditionKind $kind): bool
    {
        return $kind === QuestConditionKind::QUESTS_VALIDATED_COUNT;
    }

    public function getTarget(array $params): int
    {
        return (int) ($params['count'] ?? 0);
    }

    public function appliesToLog(array $params, HabitLog $habitLog): bool
    {
        return false;
    }

    public function incrementForLog(array $params, HabitLog $habitLog): int
    {
        return 0;
    }

    public function recomputeCurrent(array $params, User $user, \DateTimeImmutable $since): ?int
    {
        return $this->actionLogRepository->countValidationsForUserSince($user, $since);
    }
}

