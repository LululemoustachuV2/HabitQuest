<?php

namespace App\Service;

use App\Entity\HabitLog;
use App\Entity\QuestCondition;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\UserQuestStatus;
use App\Repository\QuestConditionRepository;
use App\Repository\UserQuestRepository;
use App\Service\Quest\Evaluator\ConditionEvaluatorRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class QuestProgressService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestConditionRepository $questConditionRepository,
        private readonly ConditionEvaluatorRegistry $evaluatorRegistry,
        private readonly QuestValidationService $questValidationService,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function updateQuestsAfterHabitLog(User $user, HabitLog $habitLog): void
    {
        $userQuests = $this->userQuestRepository->findInProgressWithConditionsForUser($user);
        if ($userQuests === []) {
            return;
        }

        $completedIds = [];

        foreach ($userQuests as $userQuest) {
            if ($this->updateProgressForQuest($userQuest, $habitLog)) {
                $completedIds[] = $userQuest->getId();
            }
        }

        if ($completedIds !== []) {
            $this->logger->info('PMVP-023 — Quêtes auto-complétées après HabitLog.', [
                'habitLogId' => $habitLog->getId(),
                'userQuestIds' => $completedIds,
            ]);
        }

        $this->entityManager->flush();
    }

    public function updateQuestsAfterQuestValidation(User $user): void
    {
        $userQuests = $this->userQuestRepository->findInProgressWithConditionsForUser($user);
        if ($userQuests === []) {
            return;
        }

        foreach ($userQuests as $userQuest) {
            $this->updateProgressForQuest($userQuest, null);
        }

        $this->entityManager->flush();
    }

    public function buildInitialProgress(QuestTemplate $template): array
    {
        $conditions = $this->questConditionRepository->findAllForTemplate($template);

        return $this->buildProgressFromConditions($conditions);
    }

    public function buildProgressFromConditions(array $conditions): array
    {
        $entries = [];
        foreach ($conditions as $condition) {
            $evaluator = $this->evaluatorRegistry->get($condition->getKind());
            $target = $evaluator->getTarget($condition->getParams());
            $entries[] = [
                'conditionId' => $condition->getId(),
                'kind' => $condition->getKind()->value,
                'current' => 0,
                'target' => $target,
                'satisfied' => $target <= 0,
            ];
        }

        return $this->finalizeProgressPayload($entries);
    }

    public function formatProgressForApi(?array $progress, bool $hasConditions): ?array
    {
        if (!$hasConditions || $progress === null || $progress === []) {
            return null;
        }

        $overall = $progress['overall'] ?? ['current' => 0, 'target' => 0];

        return [
            'conditions' => $progress['conditions'] ?? [],
            'overall' => $overall,
            'current' => (int) ($overall['current'] ?? 0),
            'target' => (int) ($overall['target'] ?? 0),
        ];
    }

    public function templateHasConditions(QuestTemplate $template): bool
    {
        return $this->questConditionRepository->count(['questTemplate' => $template]) > 0;
    }

    private function updateProgressForQuest(UserQuest $userQuest, ?HabitLog $habitLog): bool
    {
        if ($userQuest->getStatus() !== UserQuestStatus::IN_PROGRESS) {
            return false;
        }

        $template = $userQuest->getQuestTemplate();
        $conditions = $this->questConditionRepository->findAllForTemplate($template);
        if ($conditions === []) {
            return false;
        }

        $progress = $userQuest->getProgress();
        if ($progress === [] || !isset($progress['conditions'])) {
            $progress = $this->buildProgressFromConditions($conditions);
        }

        $since = $userQuest->getStartedAt();
        $user = $userQuest->getUser();
        $entriesById = [];
        foreach ($progress['conditions'] as $index => $entry) {
            $entriesById[(int) ($entry['conditionId'] ?? 0)] = $index;
        }

        foreach ($conditions as $condition) {
            $conditionId = $condition->getId();
            if ($conditionId === null) {
                continue;
            }

            if (!isset($entriesById[$conditionId])) {
                $progress['conditions'][] = [
                    'conditionId' => $conditionId,
                    'kind' => $condition->getKind()->value,
                    'current' => 0,
                    'target' => $this->evaluatorRegistry->get($condition->getKind())->getTarget($condition->getParams()),
                    'satisfied' => false,
                ];
                $entriesById[$conditionId] = count($progress['conditions']) - 1;
            }

            $idx = $entriesById[$conditionId];
            $entry = &$progress['conditions'][$idx];
            $evaluator = $this->evaluatorRegistry->get($condition->getKind());
            $params = $condition->getParams();
            $target = (int) ($entry['target'] ?? $evaluator->getTarget($params));

            $recomputed = $evaluator->recomputeCurrent($params, $user, $since);
            if ($recomputed !== null) {
                $entry['current'] = min($target, max(0, $recomputed));
            } elseif ($habitLog !== null && $evaluator->appliesToLog($params, $habitLog)) {
                $increment = $evaluator->incrementForLog($params, $habitLog);
                $entry['current'] = min($target, (int) ($entry['current'] ?? 0) + $increment);
            }

            $entry['target'] = $target;
            $entry['satisfied'] = (int) ($entry['current'] ?? 0) >= $target;
            unset($entry);
        }

        $progress = $this->finalizeProgressPayload($progress['conditions']);
        $userQuest->setProgress($progress);

        if (!$this->allConditionsSatisfied($progress)) {
            return false;
        }

        $this->questValidationService->completeQuestAfterConditions(
            $user,
            $userQuest,
            'Complétion automatique (conditions remplies).'
        );

        return true;
    }

    private function finalizeProgressPayload(array $conditionEntries): array
    {
        $currentSum = 0;
        $targetSum = 0;
        foreach ($conditionEntries as $entry) {
            $currentSum += (int) ($entry['current'] ?? 0);
            $targetSum += (int) ($entry['target'] ?? 0);
        }

        return [
            'conditions' => $conditionEntries,
            'overall' => [
                'current' => $currentSum,
                'target' => $targetSum,
            ],
        ];
    }

    private function allConditionsSatisfied(array $progress): bool
    {
        $conditions = $progress['conditions'] ?? [];
        if ($conditions === []) {
            return false;
        }

        foreach ($conditions as $entry) {
            if (!($entry['satisfied'] ?? false)) {
                return false;
            }
        }

        return true;
    }
}

