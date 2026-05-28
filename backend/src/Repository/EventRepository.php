<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findActiveAt(\DateTimeImmutable $at): array
    {
        $events = $this->createQueryBuilder('e')
            ->andWhere('e.startsAt <= :at')
            ->andWhere('e.endsAt >= :at')
            ->setParameter('at', $at)
            ->getQuery()
            ->getResult();

        return $events;
    }
}

