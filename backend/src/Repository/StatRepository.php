<?php

namespace App\Repository;

use App\Entity\Stat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stat::class);
    }

    public function findOneByUser(User $user): ?Stat
    {
        return $this->findOneBy(['user' => $user]);
    }
}

