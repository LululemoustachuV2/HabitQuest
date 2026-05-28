<?php

namespace App\Service;

use App\Enum\QuestConditionKind;
use App\Repository\CategoryRepository;
use App\Repository\HabitRepository;
use Symfony\Component\HttpFoundation\Response;

final class QuestConditionParamsValidator
{
    public function __construct(
        private readonly HabitRepository $habitRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function validate(QuestConditionKind $kind, array $params): ?array
    {
        return match ($kind) {
            QuestConditionKind::HABIT_LOGS_COUNT => $this->validateHabitLogsCount($params),
            QuestConditionKind::CATEGORY_LOGS_COUNT => $this->validateCategoryLogsCount($params),
            QuestConditionKind::XP_GAINED => $this->validateThreshold($params, 'amount'),
            QuestConditionKind::GOLD_GAINED => $this->validateThreshold($params, 'amount'),
            QuestConditionKind::STREAK_DAYS => $this->validateStreakDays($params),
            QuestConditionKind::QUESTS_VALIDATED_COUNT => $this->validateQuestsValidatedCount($params),
        };
    }

    private function validateQuestsValidatedCount(array $params): ?array
    {
        $countError = $this->requirePositiveInt($params, 'count');
        if ($countError !== null) {
            return $countError;
        }

        return $this->rejectUnknownKeys($params, ['count']);
    }

    private function validateHabitLogsCount(array $params): ?array
    {
        $countError = $this->requirePositiveInt($params, 'count');
        if ($countError !== null) {
            return $countError;
        }

        if (array_key_exists('habitId', $params)) {
            $habitError = $this->requirePositiveInt($params, 'habitId');
            if ($habitError !== null) {
                return $habitError;
            }

            $habitId = (int) $params['habitId'];
            if ($this->habitRepository->find($habitId) === null) {
                return $this->error('params.habitId', 'Habitude introuvable.');
            }
        }

        return $this->rejectUnknownKeys($params, ['count', 'habitId']);
    }

    private function validateCategoryLogsCount(array $params): ?array
    {
        $countError = $this->requirePositiveInt($params, 'count');
        if ($countError !== null) {
            return $countError;
        }

        $categoryError = $this->requirePositiveInt($params, 'categoryId');
        if ($categoryError !== null) {
            return $categoryError;
        }

        $categoryId = (int) $params['categoryId'];
        if ($this->categoryRepository->find($categoryId) === null) {
            return $this->error('params.categoryId', 'Catégorie introuvable.');
        }

        return $this->rejectUnknownKeys($params, ['count', 'categoryId']);
    }

    private function validateThreshold(array $params, string $field): ?array
    {
        $error = $this->requirePositiveInt($params, $field);
        if ($error !== null) {
            return $error;
        }

        return $this->rejectUnknownKeys($params, [$field]);
    }

    private function validateStreakDays(array $params): ?array
    {
        $daysError = $this->requirePositiveInt($params, 'days');
        if ($daysError !== null) {
            return $daysError;
        }

        if (array_key_exists('habitId', $params)) {
            $habitError = $this->requirePositiveInt($params, 'habitId');
            if ($habitError !== null) {
                return $habitError;
            }

            $habitId = (int) $params['habitId'];
            if ($this->habitRepository->find($habitId) === null) {
                return $this->error('params.habitId', 'Habitude introuvable.');
            }
        }

        return $this->rejectUnknownKeys($params, ['days', 'habitId']);
    }

    private function rejectUnknownKeys(array $params, array $allowedKeys): ?array
    {
        $unknown = array_diff(array_keys($params), $allowedKeys);
        if ($unknown !== []) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Paramètres inconnus pour ce type de condition.',
                'errors' => ['params' => sprintf('Clés non autorisées : %s.', implode(', ', $unknown))],
            ];
        }

        return null;
    }

    private function requirePositiveInt(array $params, string $key): ?array
    {
        if (!array_key_exists($key, $params)) {
            return $this->error('params.'.$key, sprintf('Le champ « %s » est obligatoire.', $key));
        }

        $value = $params[$key];
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            return $this->error('params.'.$key, sprintf('Le champ « %s » doit être un entier strictement positif.', $key));
        }

        $intValue = (int) $value;
        if ($intValue <= 0) {
            return $this->error('params.'.$key, sprintf('Le champ « %s » doit être strictement positif.', $key));
        }

        return null;
    }

    private function error(string $path, string $message): array
    {
        return [
            'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Paramètres de condition invalides.',
            'errors' => [$path => $message],
        ];
    }
}

