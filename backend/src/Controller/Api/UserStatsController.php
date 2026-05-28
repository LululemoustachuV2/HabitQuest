<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\StatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserStatsController extends AbstractController
{
    public function __construct(
        private readonly StatService $statService,
    ) {
    }

    #[Route('/api/user/stats', name: 'api_user_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $user = $this->getUser();

        $xp = $user->getXp();
        $level = $this->computeLevel($xp);

        $stat = $this->statService->getOrCreateForUser($user);

        return $this->json([
            'level' => $level,
            'xp' => $xp,
            'gold' => $user->getGold(),
            'stats' => $this->statService->toArray($stat),
        ]);
    }

    private function computeLevel(int $xp): int
    {
        $remainingXp = max(0, $xp);
        $level = 1;
        $xpRequiredForLevel = 100;

        while ($remainingXp >= $xpRequiredForLevel) {
            $remainingXp -= $xpRequiredForLevel;
            ++$level;
            $xpRequiredForLevel = (int) ceil($xpRequiredForLevel * 1.05);
        }

        return $level;
    }
}

