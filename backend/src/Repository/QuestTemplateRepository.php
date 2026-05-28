<?php

namespace App\Repository;

use App\Entity\QuestTemplate;
use App\Enum\QuestKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestTemplate::class);
    }

    public function findActiveByKind(QuestKind $kind): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.kind = :kind')
            ->andWhere('q.isActive = true')
            ->setParameter('kind', $kind)
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveStandard(): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.isActive = true')
            ->andWhere('q.kind != :event')
            ->setParameter('event', QuestKind::EVENT)
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

