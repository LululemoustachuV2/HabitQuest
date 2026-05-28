<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserMonster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserMonsterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMonster::class);
    }

    public function findActiveForUser(User $user): ?UserMonster
    {
        return $this->findOneBy([
            'user' => $user,
            'isActive' => true,
        ]);
    }

    public function deactivateAllForUser(User $user): void
    {
        $this->createQueryBuilder('um')
            ->update()
            ->set('um.isActive', ':inactive')
            ->where('um.user = :user')
            ->andWhere('um.isActive = true')
            ->setParameter('inactive', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function countDefeatedForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('um')
            ->select('COUNT(um.id)')
            ->andWhere('um.user = :user')
            ->andWhere('um.isActive = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

