<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ShopController extends AbstractController
{
    public function __construct(
        private readonly ShopService $shopService,
    ) {
    }

    #[Route('/api/shop', name: 'api_shop_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $result = $this->shopService->getShop($user);

        return $this->json([
            'rotationDate' => $result['rotationDate'],
            'items' => $result['items'],
        ], $result['statusCode']);
    }

    #[Route('/api/shop/purchase/{itemId}', name: 'api_shop_purchase', methods: ['POST'], requirements: ['itemId' => '\d+'])]
    public function purchase(int $itemId): JsonResponse
    {
        $user = $this->getUser();
        $result = $this->shopService->purchase($user, $itemId);

        return $this->json($result, $result['statusCode']);
    }
}

