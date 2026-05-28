<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserMonsterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MonsterController extends AbstractController
{
    public function __construct(
        private readonly UserMonsterService $userMonsterService,
    ) {
    }

    #[Route('/api/monster/active', name: 'api_monster_active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        $user = $this->getUser();

        $monster = $this->userMonsterService->getOrSpawnActiveMonster($user);

        return $this->json([
            'monster' => $this->userMonsterService->toArray($monster, $user),
        ]);
    }
}

