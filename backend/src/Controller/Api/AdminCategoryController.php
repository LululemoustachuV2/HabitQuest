<?php

namespace App\Controller\Api;

use App\Dto\CategoryDto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminCategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/categories', name: 'api_admin_category_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->categoryRepository->findBy([], ['name' => 'ASC']);
        $items = array_map(
            fn (Category $category): array => $this->categoryService->toArray($category),
            $categories
        );

        return $this->json(['items' => $items]);
    }

    #[Route('/api/admin/categories', name: 'api_admin_category_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, CategoryDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->categoryService->createCategory($dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/categories/{id}', name: 'api_admin_category_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, CategoryDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->categoryService->updateCategory($id, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/categories/{id}', name: 'api_admin_category_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $result = $this->categoryService->deleteCategory($id);

        return $this->json($result, $result['statusCode']);
    }

    private function deserializeAndValidate(Request $request, string $dtoClass): object
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, 'json');
        } catch (\Throwable) {
            return $this->json(['message' => 'Corps de requête JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['message' => 'Validation échouée.', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $dto;
    }
}

