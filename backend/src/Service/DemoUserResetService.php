<?php

namespace App\Service;

use App\Entity\Habit;
use App\Entity\HabitLog;
use App\Entity\Inventory;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Entity\UserMonster;
use App\Entity\UserQuest;
use App\Entity\UserQuestActionLog;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DemoUserResetService
{
    public const DEMO_USER_EMAIL = 'user@habitquest.dev';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly StatService $statService,
    ) {
    }

    public function resetDemoUser(string $email = self::DEMO_USER_EMAIL): int
    {
        $user = $this->userRepository->findOneByEmail($email);
        if (!$user instanceof User) {
            return 0;
        }

        $removed = 0;
        $removed += $this->deleteByDql(
            'DELETE FROM '.UserQuestActionLog::class.' l WHERE l.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.UserQuest::class.' uq WHERE uq.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.HabitLog::class.' hl WHERE hl.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.Habit::class.' h WHERE h.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.Inventory::class.' i WHERE i.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.UserMonster::class.' um WHERE um.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.UserAchievement::class.' ua WHERE ua.user = :user',
            ['user' => $user],
        );
        $removed += $this->deleteByDql(
            'DELETE FROM '.Notification::class.' n WHERE n.user = :user',
            ['user' => $user],
        );

        $user->setXp(0);
        $user->setGold(0);
        $user->setCurrentStreak(0);
        $user->setLongestStreak(0);
        $user->setLastStreakDate(null);

        $stat = $this->statService->getOrCreateForUser($user);
        $stat->resetValues(0, 0, 0, 0);

        $this->entityManager->flush();

        return $removed;
    }

    private function deleteByDql(string $dql, array $params): int
    {
        $query = $this->entityManager->createQuery($dql);
        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }

        return $query->execute();
    }
}

