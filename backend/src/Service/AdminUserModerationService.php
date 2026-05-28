<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class AdminUserModerationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly AdminAuditLogService $auditLogService,
        private readonly UserMonsterService $userMonsterService,
        private readonly LevelService $levelService,
    ) {
    }

    public function grantXp(User $admin, int $targetUserId, int $amount, string $reason): array
    {
        $target = $this->findTargetUser($targetUserId);
        if (is_array($target)) {
            return $target;
        }

        $previousXp = $target->getXp();
        $target->addXp($amount);

        $this->auditLogService->log(
            $admin,
            AdminAuditLogService::ACTION_GRANT_XP,
            AdminAuditLogService::userTarget($target),
            [
                'reason' => $reason,
                'amount' => $amount,
                'previousXp' => $previousXp,
                'newXp' => $target->getXp(),
            ],
        );

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'XP accordés.',
            'user' => $this->userSnapshot($target),
        ];
    }

    public function grantGold(User $admin, int $targetUserId, int $amount, string $reason): array
    {
        $target = $this->findTargetUser($targetUserId);
        if (is_array($target)) {
            return $target;
        }

        $previousGold = $target->getGold();
        $target->addGold($amount);

        $this->auditLogService->log(
            $admin,
            AdminAuditLogService::ACTION_GRANT_GOLD,
            AdminAuditLogService::userTarget($target),
            [
                'reason' => $reason,
                'amount' => $amount,
                'previousGold' => $previousGold,
                'newGold' => $target->getGold(),
            ],
        );

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Or accordé.',
            'user' => $this->userSnapshot($target),
        ];
    }

    public function respawnMonster(User $admin, int $targetUserId, string $reason): array
    {
        $target = $this->findTargetUser($targetUserId);
        if (is_array($target)) {
            return $target;
        }

        try {
            $monster = $this->userMonsterService->spawnForUser($target);
        } catch (\RuntimeException $e) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => $e->getMessage(),
            ];
        }

        $this->auditLogService->log(
            $admin,
            AdminAuditLogService::ACTION_RESPAWN_MONSTER,
            AdminAuditLogService::userTarget($target),
            [
                'reason' => $reason,
                'monsterId' => $monster->getId(),
                'templateId' => $monster->getTemplate()->getId(),
            ],
        );

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Monstre respawné.',
            'monster' => $this->userMonsterService->toArray($monster, $target),
        ];
    }

    private function findTargetUser(int $targetUserId): User|array
    {
        $target = $this->userRepository->find($targetUserId);
        if (!$target instanceof User) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Utilisateur introuvable.',
            ];
        }

        return $target;
    }

    private function userSnapshot(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'xp' => $user->getXp(),
            'gold' => $user->getGold(),
            'level' => $this->levelService->computeLevel($user->getXp()),
        ];
    }
}

