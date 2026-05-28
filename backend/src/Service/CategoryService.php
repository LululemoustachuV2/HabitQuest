<?php

namespace App\Service;

use App\Dto\CategoryDto;
use App\Entity\Category;
use App\Enum\StatType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function createCategory(CategoryDto $dto): array
    {
        $name = trim((string) $dto->name);

        if ($this->categoryRepository->findOneByName($name) instanceof Category) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Une catégorie avec ce nom existe déjà.',
            ];
        }

        $category = (new Category())
            ->setName($name)
            ->setLinkedStat(StatType::from((string) $dto->linkedStat));

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Catégorie créée.',
            'category' => $this->toArray($category),
        ];
    }

    public function updateCategory(int $id, CategoryDto $dto): array
    {
        $category = $this->categoryRepository->find($id);
        if (!$category instanceof Category) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Catégorie introuvable.',
            ];
        }

        $name = trim((string) $dto->name);
        $existing = $this->categoryRepository->findOneByName($name);
        if ($existing instanceof Category && $existing->getId() !== $category->getId()) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Une autre catégorie utilise déjà ce nom.',
            ];
        }

        $category
            ->setName($name)
            ->setLinkedStat(StatType::from((string) $dto->linkedStat));

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Catégorie mise à jour.',
            'category' => $this->toArray($category),
        ];
    }

    public function deleteCategory(int $id): array
    {
        $category = $this->categoryRepository->find($id);
        if (!$category instanceof Category) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Catégorie introuvable.',
            ];
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Catégorie supprimée.',
        ];
    }

    public function toArray(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'linkedStat' => $category->getLinkedStat()->value,
        ];
    }
}

