<?php

namespace App\Repository;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Enum\AchievementCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserAchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAchievement::class);
    }

    public function hasUnlocked(User $user, Achievement $achievement): bool
    {
        return $this->count([
            'user' => $user,
            'achievement' => $achievement,
        ]) > 0;
    }

    public function hasUnlockedCode(User $user, AchievementCode $code): bool
    {
        return (int) $this->createQueryBuilder('ua')
            ->select('COUNT(ua.id)')
            ->innerJoin('ua.achievement', 'a')
            ->andWhere('ua.user = :user')
            ->andWhere('a.code = :code')
            ->setParameter('user', $user)
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('ua')
            ->innerJoin('ua.achievement', 'a')
            ->addSelect('a')
            ->andWhere('ua.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ua.unlockedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

