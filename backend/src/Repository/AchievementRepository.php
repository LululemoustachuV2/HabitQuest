<?php

namespace App\Repository;

use App\Entity\Achievement;
use App\Enum\AchievementCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AchievementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Achievement::class);
    }

    public function findOneByCode(AchievementCode $code): ?Achievement
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

