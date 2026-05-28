<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\AchievementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AchievementController extends AbstractController
{
    public function __construct(
        private readonly AchievementService $achievementService,
    ) {
    }

    #[Route('/api/achievements', name: 'api_achievements_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'achievements' => $this->achievementService->listForUser($user),
            'currentStreak' => $user->getCurrentStreak(),
            'longestStreak' => $user->getLongestStreak(),
        ]);
    }
}

