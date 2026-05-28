<?php

namespace App\Controller\Api;

use App\Service\HabitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminHabitController extends AbstractController
{
    public function __construct(
        private readonly HabitService $habitService,
    ) {
    }

    #[Route('/api/admin/habits', name: 'api_admin_habits_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json([
            'items' => $this->habitService->listAll(),
        ]);
    }
}

