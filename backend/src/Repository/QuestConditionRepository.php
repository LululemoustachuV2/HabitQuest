<?php

namespace App\Repository;

use App\Entity\QuestCondition;
use App\Entity\QuestTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestConditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestCondition::class);
    }

    public function findAllForTemplate(QuestTemplate $template): array
    {
        return $this->createQueryBuilder('qc')
            ->andWhere('qc.questTemplate = :template')
            ->setParameter('template', $template)
            ->orderBy('qc.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

