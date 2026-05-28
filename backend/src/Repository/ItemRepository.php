<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findSellableWithPrice(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.isSellable = true')
            ->andWhere('i.shopPrice IS NOT NULL')
            ->andWhere('i.shopPrice > 0')
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

