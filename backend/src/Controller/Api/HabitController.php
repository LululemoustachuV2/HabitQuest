<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\HabitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HabitController extends AbstractController
{
    public function __construct(
        private readonly HabitService $habitService,
    ) {
    }

    #[Route('/api/habits', name: 'api_habits_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'items' => $this->habitService->listForUser($user),
        ]);
    }

    #[Route('/api/habits', name: 'api_habits_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->json([
            'message' => 'La création d\'habitudes par le joueur n\'est plus disponible. Accomplissez les quêtes proposées par l\'administrateur.',
        ], Response::HTTP_FORBIDDEN);
    }

    #[Route('/api/habits/{id}', name: 'api_habits_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json([
            'message' => 'La modification d\'habitudes par le joueur n\'est plus disponible.',
        ], Response::HTTP_FORBIDDEN);
    }

    #[Route('/api/habits/{id}', name: 'api_habits_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        return $this->json([
            'message' => 'La suppression d\'habitudes par le joueur n\'est plus disponible.',
        ], Response::HTTP_FORBIDDEN);
    }
}

