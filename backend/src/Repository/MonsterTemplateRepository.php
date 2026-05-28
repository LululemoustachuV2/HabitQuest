<?php

namespace App\Repository;

use App\Entity\MonsterTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MonsterTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonsterTemplate::class);
    }

    public function findOneByName(string $name): ?MonsterTemplate
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findEligibleForLevel(int $playerLevel, ?int $excludeTemplateId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.levelMin <= :level')
            ->andWhere('t.levelMax >= :level')
            ->setParameter('level', $playerLevel)
            ->orderBy('t.id', 'ASC');

        if ($excludeTemplateId !== null) {
            $qb->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeTemplateId);
        }

        $eligible = $qb->getQuery()->getResult();

        if ($eligible !== [] || $excludeTemplateId === null) {
            return $eligible;
        }

        return $this->findEligibleForLevel($playerLevel, null);
    }
}

