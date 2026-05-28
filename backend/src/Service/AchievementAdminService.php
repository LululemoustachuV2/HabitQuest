<?php

namespace App\Service;

use App\Dto\AchievementDto;
use App\Entity\Achievement;
use App\Enum\AchievementCode;
use App\Repository\AchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class AchievementAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AchievementRepository $achievementRepository,
    ) {
    }

    public function list(): array
    {
        $items = array_map(
            fn (Achievement $a): array => $this->toArray($a),
            $this->achievementRepository->findAllOrdered(),
        );

        return [
            'statusCode' => Response::HTTP_OK,
            'items' => $items,
        ];
    }

    public function create(AchievementDto $dto): array
    {
        $code = AchievementCode::from((string) $dto->code);
        if ($this->achievementRepository->findOneByCode($code) instanceof Achievement) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Un achievement avec ce code existe déjà.',
            ];
        }

        $achievement = (new Achievement())
            ->setCode($code)
            ->setName((string) $dto->name)
            ->setDescription((string) $dto->description);

        $this->entityManager->persist($achievement);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Achievement créé.',
            'achievement' => $this->toArray($achievement),
        ];
    }

    public function update(int $id, AchievementDto $dto): array
    {
        $achievement = $this->achievementRepository->find($id);
        if (!$achievement instanceof Achievement) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Achievement introuvable.',
            ];
        }

        $newCode = AchievementCode::from((string) $dto->code);
        $existing = $this->achievementRepository->findOneByCode($newCode);
        if ($existing instanceof Achievement && $existing->getId() !== $achievement->getId()) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Ce code est déjà utilisé par un autre achievement.',
            ];
        }

        $achievement
            ->setCode($newCode)
            ->setName((string) $dto->name)
            ->setDescription((string) $dto->description);

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Achievement mis à jour.',
            'achievement' => $this->toArray($achievement),
        ];
    }

    public function delete(int $id): array
    {
        $achievement = $this->achievementRepository->find($id);
        if (!$achievement instanceof Achievement) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Achievement introuvable.',
            ];
        }

        $this->entityManager->remove($achievement);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Achievement supprimé.',
        ];
    }

    public function toArray(Achievement $achievement): array
    {
        return [
            'id' => $achievement->getId(),
            'code' => $achievement->getCode()->value,
            'name' => $achievement->getName(),
            'description' => $achievement->getDescription(),
        ];
    }
}

