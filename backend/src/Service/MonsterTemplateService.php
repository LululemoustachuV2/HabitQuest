<?php

namespace App\Service;

use App\Dto\MonsterTemplateDto;
use App\Entity\MonsterTemplate;
use App\Enum\AffinityStat;
use App\Enum\Rarity;
use App\Repository\ItemRepository;
use App\Repository\MonsterTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class MonsterTemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MonsterTemplateRepository $monsterTemplateRepository,
        private readonly ItemRepository $itemRepository,
    ) {
    }

    public function createTemplate(MonsterTemplateDto $dto): array
    {
        $levelError = $this->validateLevelRange((int) $dto->levelMin, (int) $dto->levelMax);
        if ($levelError !== null) {
            return $levelError;
        }

        $lootError = $this->validateLootTable($dto->lootTable ?? []);
        if ($lootError !== null) {
            return $lootError;
        }

        $template = new MonsterTemplate();
        $this->hydrateFromDto($template, $dto);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Modèle de monstre créé.',
            'monster' => $this->toArray($template),
        ];
    }

    public function updateTemplate(int $id, MonsterTemplateDto $dto): array
    {
        $template = $this->monsterTemplateRepository->find($id);
        if (!$template instanceof MonsterTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de monstre introuvable.',
            ];
        }

        $levelError = $this->validateLevelRange((int) $dto->levelMin, (int) $dto->levelMax);
        if ($levelError !== null) {
            return $levelError;
        }

        $lootError = $this->validateLootTable($dto->lootTable ?? []);
        if ($lootError !== null) {
            return $lootError;
        }

        $this->hydrateFromDto($template, $dto);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Modèle de monstre mis à jour.',
            'monster' => $this->toArray($template),
        ];
    }

    public function deleteTemplate(int $id): array
    {
        $template = $this->monsterTemplateRepository->find($id);
        if (!$template instanceof MonsterTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de monstre introuvable.',
            ];
        }

        $this->entityManager->remove($template);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Modèle de monstre supprimé.',
        ];
    }

    public function getTemplate(int $id): array
    {
        $template = $this->monsterTemplateRepository->find($id);
        if (!$template instanceof MonsterTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de monstre introuvable.',
            ];
        }

        return [
            'statusCode' => Response::HTTP_OK,
            'monster' => $this->toArray($template),
        ];
    }

    public function toArray(MonsterTemplate $template): array
    {
        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'baseHp' => $template->getBaseHp(),
            'levelMin' => $template->getLevelMin(),
            'levelMax' => $template->getLevelMax(),
            'rarity' => $template->getRarity()->value,
            'affinityStat' => $template->getAffinityStat()->value,
            'lootTable' => $template->getLootTable(),
            'imageUrl' => $template->getImageUrl(),
        ];
    }

    private function hydrateFromDto(MonsterTemplate $template, MonsterTemplateDto $dto): void
    {
        $template
            ->setName((string) $dto->name)
            ->setBaseHp((int) $dto->baseHp)
            ->setLevelMin((int) $dto->levelMin)
            ->setLevelMax((int) $dto->levelMax)
            ->setRarity(Rarity::from((string) $dto->rarity))
            ->setAffinityStat(AffinityStat::from((string) $dto->affinityStat))
            ->setLootTable($this->normalizeLootTable($dto->lootTable ?? []))
            ->setImageUrl($dto->imageUrl);
    }

    private function normalizeLootTable(array $lootTable): array
    {
        $normalized = [];
        foreach ($lootTable as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $itemId = isset($entry['itemId']) ? (int) $entry['itemId'] : 0;
            $weight = isset($entry['weight']) ? (int) $entry['weight'] : 0;
            if ($itemId <= 0 || $weight <= 0) {
                continue;
            }
            $normalized[] = ['itemId' => $itemId, 'weight' => $weight];
        }

        return $normalized;
    }

    private function validateLootTable(array $lootTable): ?array
    {
        if ($lootTable === []) {
            return null;
        }

        foreach ($lootTable as $index => $entry) {
            if (!is_array($entry)) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation échouée.',
                    'errors' => ['lootTable' => sprintf('Entrée %d invalide.', $index)],
                ];
            }

            $itemId = isset($entry['itemId']) ? (int) $entry['itemId'] : 0;
            $weight = isset($entry['weight']) ? (int) $entry['weight'] : 0;

            if ($itemId <= 0 || $weight <= 0) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation échouée.',
                    'errors' => ['lootTable' => 'Chaque entrée doit avoir itemId et weight positifs.'],
                ];
            }

            if ($this->itemRepository->find($itemId) === null) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation échouée.',
                    'errors' => ['lootTable' => sprintf('Item %d introuvable.', $itemId)],
                ];
            }
        }

        return null;
    }

    private function validateLevelRange(int $levelMin, int $levelMax): ?array
    {
        if ($levelMin > $levelMax) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Validation échouée.',
                'errors' => ['levelMin' => 'Le niveau minimum ne peut pas dépasser le niveau maximum.'],
            ];
        }

        return null;
    }
}

