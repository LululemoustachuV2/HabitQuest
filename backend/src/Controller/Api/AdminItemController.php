<?php

namespace App\Controller\Api;

use App\Dto\ItemDto;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Service\ItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminItemController extends AbstractController
{
    public function __construct(
        private readonly ItemService $itemService,
        private readonly ItemRepository $itemRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/items', name: 'api_admin_item_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->itemRepository->findBy([], ['id' => 'DESC']);
        $payload = array_map(
            fn (Item $item): array => $this->itemService->toArray($item),
            $items
        );

        return $this->json(['items' => $payload]);
    }

    #[Route('/api/admin/items/{id}', name: 'api_admin_item_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $result = $this->itemService->getItem($id);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/items', name: 'api_admin_item_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, ItemDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->itemService->createItem($dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/items/{id}', name: 'api_admin_item_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, ItemDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->itemService->updateItem($id, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/items/{id}', name: 'api_admin_item_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $result = $this->itemService->deleteItem($id);

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

