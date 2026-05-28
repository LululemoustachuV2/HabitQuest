<?php

namespace App\Service;

use App\Dto\QuestConditionDto;
use App\Entity\QuestCondition;
use App\Entity\QuestTemplate;
use App\Enum\QuestConditionKind;
use App\Repository\QuestConditionRepository;
use App\Repository\QuestTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class QuestConditionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly QuestConditionRepository $questConditionRepository,
        private readonly QuestConditionParamsValidator $paramsValidator,
    ) {
    }

    public function listForTemplate(int $templateId): array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $conditions = $this->questConditionRepository->findAllForTemplate($template);

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Conditions listées.',
            'items' => array_map(fn (QuestCondition $c): array => $this->toArray($c), $conditions),
        ];
    }

    public function createCondition(int $templateId, QuestConditionDto $dto): array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $kind = QuestConditionKind::from((string) $dto->kind);
        $params = $this->normalizeParams($dto->params ?? []);
        $paramsError = $this->paramsValidator->validate($kind, $params);
        if ($paramsError !== null) {
            return $paramsError;
        }

        $condition = (new QuestCondition())
            ->setQuestTemplate($template)
            ->setKind($kind)
            ->setParams($params);

        $this->entityManager->persist($condition);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Condition créée.',
            'condition' => $this->toArray($condition),
        ];
    }

    public function updateCondition(int $templateId, int $conditionId, QuestConditionDto $dto): array
    {
        $resolved = $this->findConditionForTemplate($templateId, $conditionId);
        if (!($resolved instanceof QuestCondition)) {
            return $resolved;
        }
        $condition = $resolved;

        $kind = QuestConditionKind::from((string) $dto->kind);
        $params = $this->normalizeParams($dto->params ?? []);
        $paramsError = $this->paramsValidator->validate($kind, $params);
        if ($paramsError !== null) {
            return $paramsError;
        }

        $condition
            ->setKind($kind)
            ->setParams($params);

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Condition mise à jour.',
            'condition' => $this->toArray($condition),
        ];
    }

    public function deleteCondition(int $templateId, int $conditionId): array
    {
        $resolved = $this->findConditionForTemplate($templateId, $conditionId);
        if (!($resolved instanceof QuestCondition)) {
            return $resolved;
        }

        $this->entityManager->remove($resolved);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Condition supprimée.',
        ];
    }

    public function toArray(QuestCondition $condition): array
    {
        return [
            'id' => $condition->getId(),
            'questTemplateId' => $condition->getQuestTemplate()->getId(),
            'kind' => $condition->getKind()->value,
            'params' => $condition->getParams(),
        ];
    }

    private function findTemplate(int $templateId): ?QuestTemplate
    {
        $template = $this->questTemplateRepository->find($templateId);

        return $template instanceof QuestTemplate ? $template : null;
    }

    private function findConditionForTemplate(int $templateId, int $conditionId): QuestCondition|array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $condition = $this->questConditionRepository->find($conditionId);
        if (!$condition instanceof QuestCondition || $condition->getQuestTemplate()->getId() !== $template->getId()) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Condition introuvable.',
            ];
        }

        return $condition;
    }

    private function normalizeParams(array $params): array
    {
        $normalized = [];
        foreach ($params as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (is_int($value)) {
                $normalized[$key] = $value;
            } elseif (is_string($value) && ctype_digit($value)) {
                $normalized[$key] = (int) $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}

