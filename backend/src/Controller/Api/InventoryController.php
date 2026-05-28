<?php

namespace App\Controller\Api;

use App\Entity\Inventory;
use App\Entity\User;
use App\Repository\InventoryRepository;
use App\Security\Voter\InventoryVoter;
use App\Service\InventoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InventoryController extends AbstractController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly InventoryRepository $inventoryRepository,
    ) {
    }

    #[Route('/api/inventory', name: 'api_inventory_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $limit = $this->parsePositiveInt(
            $request->query->get('limit'),
            default: InventoryService::MAX_PAGE_SIZE
        );
        $offset = $this->parsePositiveInt(
            $request->query->get('offset'),
            default: 0,
            allowZero: true
        );

        $items = $this->inventoryService->listForUser($user, $limit, $offset);

        return $this->json([
            'items' => $items,
            'limit' => min(InventoryService::MAX_PAGE_SIZE, max(1, $limit)),
            'offset' => max(0, $offset),
        ]);
    }

    #[Route('/api/inventory/{id}/equip', name: 'api_inventory_equip', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function equip(int $id): JsonResponse
    {
        $entry = $this->inventoryRepository->find($id);
        if (!$entry instanceof Inventory) {
            return $this->json(['message' => 'Entrée d\'inventaire introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(InventoryVoter::EQUIP, $entry)) {
            return $this->json(
                ['message' => 'Accès refusé : entrée d\'inventaire non détenue.'],
                Response::HTTP_FORBIDDEN
            );
        }

        $result = $this->inventoryService->toggleEquip($entry);

        if ($result['statusCode'] !== Response::HTTP_OK) {
            return $this->json(
                ['message' => $result['message'] ?? 'Équipement impossible.'],
                $result['statusCode']
            );
        }

        $equippedEntry = $result['entry'];

        return $this->json($this->inventoryService->toArray($equippedEntry));
    }

    private function parsePositiveInt(mixed $raw, int $default, bool $allowZero = false): int
    {
        if (!is_string($raw) || $raw === '' || !ctype_digit($raw)) {
            return $default;
        }

        $value = (int) $raw;

        if (!$allowZero && $value <= 0) {
            return $default;
        }

        return $value;
    }
}

