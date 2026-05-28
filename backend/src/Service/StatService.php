<?php

namespace App\Service;

use App\Entity\Stat;
use App\Entity\User;
use App\Enum\StatType;
use App\Repository\StatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class StatService
{
    public const SOURCE_HABIT_LOG = 'habit_log';
    public const SOURCE_QUEST_REWARD = 'quest_reward';
    public const SOURCE_ITEM_EQUIPPED = 'item_equipped';
    public const SOURCE_EVENT = 'event';
    public const SOURCE_ACHIEVEMENT = 'achievement';
    public const SOURCE_ADMIN_GRANT = 'admin_grant';
    public const SOURCE_REGISTRATION = 'registration';
    public const SOURCE_LEVEL_UP = 'level_up';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StatRepository $statRepository,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function initializeForUser(User $user): Stat
    {
        $existing = $this->statRepository->findOneByUser($user);
        if ($existing instanceof Stat) {
            return $existing;
        }

        $stat = (new Stat())->setUser($user);
        $this->entityManager->persist($stat);

        return $stat;
    }

    public function getOrCreateForUser(User $user): Stat
    {
        $existing = $this->statRepository->findOneByUser($user);
        if ($existing instanceof Stat) {
            return $existing;
        }

        $stat = (new Stat())->setUser($user);
        $this->entityManager->persist($stat);
        $this->entityManager->flush();

        $this->logger->info('PMVP-002 — Stat créée à la volée pour un user sans backfill.', [
            'userId' => $user->getId(),
            'source' => self::SOURCE_REGISTRATION,
        ]);

        return $stat;
    }

    public function addStatPoints(User $user, StatType $stat, int $points, string $source): Stat
    {
        if ($source === '') {
            throw new \InvalidArgumentException('Le paramètre $source de addStatPoints ne peut pas être vide.');
        }

        if ($points < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Le nombre de points à ajouter doit être positif ou nul, %d reçu (stat=%s, source=%s).',
                $points,
                $stat->value,
                $source
            ));
        }

        $statEntity = $this->getOrCreateForUser($user);

        if ($points === 0) {
            return $statEntity;
        }

        $statEntity->addPoints($stat, $points);
        $this->entityManager->flush();

        $this->logger->info('PMVP-002 — Points de stat ajoutés.', [
            'userId' => $user->getId(),
            'stat' => $stat->value,
            'points' => $points,
            'source' => $source,
            'newValue' => $statEntity->get($stat),
        ]);

        return $statEntity;
    }

    public function grantLevelUpBonuses(User $user, int $oldLevel, int $newLevel, bool $flush = true): Stat
    {
        $levelsGained = max(0, $newLevel - $oldLevel);
        if ($levelsGained === 0) {
            return $this->getOrCreateForUser($user);
        }

        $statEntity = $this->getOrCreateForUser($user);
        foreach (StatType::cases() as $statType) {
            $statEntity->addPoints($statType, $levelsGained);
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        $this->logger->info('V2.3 — Bonus de montée de niveau appliqués.', [
            'userId' => $user->getId(),
            'levelsGained' => $levelsGained,
            'oldLevel' => $oldLevel,
            'newLevel' => $newLevel,
            'source' => self::SOURCE_LEVEL_UP,
        ]);

        return $statEntity;
    }

    public function toArray(Stat $stat): array
    {
        return $stat->toArray();
    }
}

