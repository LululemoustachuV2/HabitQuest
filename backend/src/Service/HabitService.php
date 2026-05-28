<?php

namespace App\Service;

use App\Dto\HabitDto;
use App\Dto\HabitUpdateDto;
use App\Entity\Category;
use App\Entity\Habit;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\HabitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class HabitService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HabitRepository $habitRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function createHabit(User $user, HabitDto $dto): array
    {
        $habit = (new Habit())
            ->setUser($user)
            ->setName(trim((string) $dto->name))
            ->setDescription((string) ($dto->description ?? ''))
            ->setXpReward((int) ($dto->xpReward ?? 0))
            ->setGoldReward((int) ($dto->goldReward ?? 0))
            ->setIsActive($dto->isActive ?? true);

        if ($dto->categoryId !== null) {
            $category = $this->categoryRepository->find($dto->categoryId);
            if (!$category instanceof Category) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Validation échouée.',
                    'errors' => ['categoryId' => 'Catégorie introuvable.'],
                ];
            }
            $habit->setCategory($category);
        }

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Habitude créée.',
            'habit' => $this->toArray($habit),
        ];
    }

    public function updateHabit(Habit $habit, HabitUpdateDto $dto, array $providedKeys): array
    {
        if (array_key_exists('name', $providedKeys)) {
            $habit->setName(trim((string) $dto->name));
        }

        if (array_key_exists('description', $providedKeys)) {
            $habit->setDescription((string) ($dto->description ?? ''));
        }

        if (array_key_exists('xpReward', $providedKeys)) {
            $habit->setXpReward((int) ($dto->xpReward ?? 0));
        }

        if (array_key_exists('goldReward', $providedKeys)) {
            $habit->setGoldReward((int) ($dto->goldReward ?? 0));
        }

        if (array_key_exists('isActive', $providedKeys)) {
            $habit->setIsActive((bool) ($dto->isActive ?? false));
        }

        if (array_key_exists('categoryId', $providedKeys)) {
            if ($dto->categoryId === null) {
                $habit->setCategory(null);
            } else {
                $category = $this->categoryRepository->find($dto->categoryId);
                if (!$category instanceof Category) {
                    return [
                        'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'message' => 'Validation échouée.',
                        'errors' => ['categoryId' => 'Catégorie introuvable.'],
                    ];
                }
                $habit->setCategory($category);
            }
        }

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Habitude mise à jour.',
            'habit' => $this->toArray($habit),
        ];
    }

    public function deleteHabit(Habit $habit): array
    {
        $this->entityManager->remove($habit);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Habitude supprimée.',
        ];
    }

    public function listForUser(User $user): array
    {
        return array_map(
            fn (Habit $habit): array => $this->toArray($habit),
            $this->habitRepository->findAllForUser($user)
        );
    }

    public function listAll(): array
    {
        return array_map(
            fn (Habit $habit): array => $this->toArray($habit, includeOwner: true),
            $this->habitRepository->findAllOrdered()
        );
    }

    public function toArray(Habit $habit, bool $includeOwner = false): array
    {
        $payload = [
            'id' => $habit->getId(),
            'name' => $habit->getName(),
            'description' => $habit->getDescription(),
            'xpReward' => $habit->getXpReward(),
            'goldReward' => $habit->getGoldReward(),
            'isActive' => $habit->isActive(),
            'createdAt' => $habit->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'category' => $habit->getCategory() !== null ? [
                'id' => $habit->getCategory()->getId(),
                'name' => $habit->getCategory()->getName(),
                'linkedStat' => $habit->getCategory()->getLinkedStat()->value,
            ] : null,
        ];

        if ($includeOwner) {
            $payload['user'] = [
                'id' => $habit->getUser()->getId(),
                'email' => $habit->getUser()->getEmail(),
            ];
        }

        return $payload;
    }
}

