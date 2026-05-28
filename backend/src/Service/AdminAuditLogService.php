<?php

namespace App\Service;

use App\Entity\AdminAuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class AdminAuditLogService
{
    public const ACTION_GRANT_XP = 'grant_xp';
    public const ACTION_GRANT_GOLD = 'grant_gold';
    public const ACTION_RESPAWN_MONSTER = 'respawn_monster';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function log(User $admin, string $action, string $target, array $payload, bool $flush = false): AdminAuditLog
    {
        $entry = (new AdminAuditLog())
            ->setAdminUser($admin)
            ->setAction($action)
            ->setTarget($target)
            ->setPayload($payload);

        $this->entityManager->persist($entry);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $entry;
    }

    public static function userTarget(User $user): string
    {
        $id = $user->getId();
        self::assertUserPersisted($id);

        return sprintf('user:%d', $id);
    }

    private static function assertUserPersisted(?int $id): void
    {
        if ($id === null) {
            throw new \LogicException('L\'utilisateur cible doit être persisté avant audit.');
        }
    }
}

