<?php

namespace App\Repository;

use App\Entity\Inventory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventory::class);
    }

    public function findAllForUser(User $user, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(0, $offset);

        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.acquiredAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findEquippedForUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.item', 'item')->addSelect('item')
            ->andWhere('i.user = :user')
            ->andWhere('i.isEquipped = true')
            ->setParameter('user', $user)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function unequipAllForUser(User $user): void
    {
        $this->createQueryBuilder('i')
            ->update()
            ->set('i.isEquipped', ':equipped')
            ->where('i.user = :user')
            ->andWhere('i.isEquipped = true')
            ->setParameter('equipped', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}

