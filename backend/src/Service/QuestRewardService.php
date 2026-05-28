<?php

namespace App\Service;

use App\Dto\QuestRewardDto;
use App\Entity\Item;
use App\Entity\QuestReward;
use App\Entity\QuestTemplate;
use App\Repository\ItemRepository;
use App\Repository\QuestRewardRepository;
use App\Repository\QuestTemplateRepository;
use App\Service\QuestRewardStatHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class QuestRewardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly QuestRewardRepository $questRewardRepository,
        private readonly ItemRepository $itemRepository,
    ) {
    }

    public function getForTemplate(int $templateId): array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $reward = $this->questRewardRepository->findOneByQuestTemplate($template);
        if (!$reward instanceof QuestReward) {
            return [
                'statusCode' => Response::HTTP_OK,
                'message' => 'Aucune récompense composée.',
                'reward' => null,
            ];
        }

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Récompense composée.',
            'reward' => $this->toArray($reward),
        ];
    }

    public function upsertReward(int $templateId, QuestRewardDto $dto): array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $item = null;
        if ($dto->itemId !== null) {
            $item = $this->itemRepository->find((int) $dto->itemId);
            if (!$item instanceof Item) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation échouée.',
                    'errors' => ['itemId' => 'Item introuvable.'],
                ];
            }
        }

        $reward = $this->questRewardRepository->findOneByQuestTemplate($template);
        $isNew = !$reward instanceof QuestReward;
        if ($isNew) {
            $reward = (new QuestReward())->setQuestTemplate($template);
            $this->entityManager->persist($reward);
        }

        $reward
            ->setXp((int) $dto->xp)
            ->setGold((int) $dto->gold)
            ->setItem($item)
            ->setParams($dto->params);

        $this->entityManager->flush();

        return [
            'statusCode' => $isNew ? Response::HTTP_CREATED : Response::HTTP_OK,
            'message' => $isNew ? 'Récompense composée créée.' : 'Récompense composée mise à jour.',
            'reward' => $this->toArray($reward),
        ];
    }

    public function deleteReward(int $templateId): array
    {
        $template = $this->findTemplate($templateId);
        if ($template === null) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $reward = $this->questRewardRepository->findOneByQuestTemplate($template);
        if (!$reward instanceof QuestReward) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Aucune récompense composée à supprimer.',
            ];
        }

        $this->entityManager->remove($reward);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Récompense composée supprimée.',
        ];
    }

    public function toArray(QuestReward $reward): array
    {
        $item = $reward->getItem();

        return [
            'id' => $reward->getId(),
            'questTemplateId' => $reward->getQuestTemplate()->getId(),
            'xp' => $reward->getXp(),
            'gold' => $reward->getGold(),
            'itemId' => $item?->getId(),
            'itemName' => $item?->getName(),
            'params' => $reward->getParams(),
            'statRewards' => QuestRewardStatHelper::formatForApi($reward->getParams()),
        ];
    }

    private function findTemplate(int $templateId): ?QuestTemplate
    {
        $template = $this->questTemplateRepository->find($templateId);

        return $template instanceof QuestTemplate ? $template : null;
    }
}

