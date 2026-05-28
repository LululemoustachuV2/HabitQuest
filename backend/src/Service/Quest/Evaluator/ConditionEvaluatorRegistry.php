<?php

namespace App\Service\Quest\Evaluator;

use App\Enum\QuestConditionKind;

final class ConditionEvaluatorRegistry
{
    private array $byKind;

    public function __construct(
        HabitLogsCountEvaluator $habitLogsCount,
        CategoryLogsCountEvaluator $categoryLogsCount,
        XpGainedEvaluator $xpGained,
        GoldGainedEvaluator $goldGained,
        StreakDaysEvaluator $streakDays,
        QuestsValidatedCountEvaluator $questsValidatedCount,
    ) {
        $this->byKind = [
            QuestConditionKind::HABIT_LOGS_COUNT->value => $habitLogsCount,
            QuestConditionKind::CATEGORY_LOGS_COUNT->value => $categoryLogsCount,
            QuestConditionKind::XP_GAINED->value => $xpGained,
            QuestConditionKind::GOLD_GAINED->value => $goldGained,
            QuestConditionKind::STREAK_DAYS->value => $streakDays,
            QuestConditionKind::QUESTS_VALIDATED_COUNT->value => $questsValidatedCount,
        ];
    }

    public function get(QuestConditionKind $kind): ConditionEvaluatorInterface
    {
        $evaluator = $this->byKind[$kind->value] ?? null;
        if (!$evaluator instanceof ConditionEvaluatorInterface) {
            throw new \InvalidArgumentException(sprintf('Aucun évaluateur pour le kind « %s ».', $kind->value));
        }

        return $evaluator;
    }
}

