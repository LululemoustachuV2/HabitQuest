<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\ShopPurchase;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShopPurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopPurchase::class);
    }

    public function findPurchasedItemIdsForUserAndDate(User $user, string $rotationDate): array
    {
        $rows = $this->createQueryBuilder('sp')
            ->select('IDENTITY(sp.item) AS itemId')
            ->where('sp.user = :user')
            ->andWhere('sp.rotationDate = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $rotationDate)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['itemId'], $rows);
    }

    public function hasPurchased(User $user, Item $item, string $rotationDate): bool
    {
        return $this->count([
            'user' => $user,
            'item' => $item,
            'rotationDate' => $rotationDate,
        ]) > 0;
    }
}

