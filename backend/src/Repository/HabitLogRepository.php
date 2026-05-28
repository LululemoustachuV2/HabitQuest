<?php

namespace App\Repository;

use App\Entity\HabitLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HabitLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitLog::class);
    }

    public function findLatestForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.loggedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForUserAndHabit(User $user, int $habitId): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.user = :user')
            ->innerJoin('l.habit', 'h')
            ->andWhere('h.id = :habitId')
            ->setParameter('user', $user)
            ->setParameter('habitId', $habitId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function computeConsecutiveStreakDaysForHabit(User $user, int $habitId): int
    {
        $userId = $user->getId();
        if ($userId === null) {
            return 0;
        }

        $sql = <<<'SQL'
            SELECT DISTINCT TO_CHAR(hl.logged_at AT TIME ZONE 'UTC' AT TIME ZONE 'Europe/Paris', 'YYYY-MM-DD') AS log_day
            FROM habit_logs hl
            WHERE hl.user_id = :userId AND hl.habit_id = :habitId
            ORDER BY log_day DESC
        SQL;

        $days = $this->getEntityManager()->getConnection()->fetchFirstColumn($sql, [
            'userId' => $userId,
            'habitId' => $habitId,
        ]);

        if ($days === []) {
            return 0;
        }

        $parisTz = new \DateTimeZone('Europe/Paris');
        $today = (new \DateTimeImmutable('now', $parisTz))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('yesterday', $parisTz))->format('Y-m-d');

        $anchor = null;
        if (in_array($today, $days, true)) {
            $anchor = $today;
        } elseif (in_array($yesterday, $days, true)) {
            $anchor = $yesterday;
        }

        if ($anchor === null) {
            return 0;
        }

        $daySet = array_flip($days);
        $streak = 0;
        $cursor = new \DateTimeImmutable($anchor.' 00:00:00', $parisTz);

        while (isset($daySet[$cursor->format('Y-m-d')])) {
            ++$streak;
            $cursor = $cursor->modify('-1 day');
        }

        return $streak;
    }

    public function countDistinctLoggedDaysForUserSince(
        User $user,
        \DateTimeImmutable $since,
        ?int $habitId = null,
    ): int {
        $sql = 'SELECT COUNT(DISTINCT CAST(hl.logged_at AS DATE)) FROM habit_logs hl'
            .' WHERE hl.user_id = :userId AND hl.logged_at >= :since';
        $params = [
            'userId' => $user->getId(),
            'since' => $since,
        ];

        if ($habitId !== null) {
            $sql .= ' AND hl.habit_id = :habitId';
            $params['habitId'] = $habitId;
        }

        return (int) $this->getEntityManager()->getConnection()->fetchOne($sql, $params);
    }
}

