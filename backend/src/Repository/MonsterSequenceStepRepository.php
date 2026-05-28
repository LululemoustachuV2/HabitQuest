<?php

namespace App\Repository;

use App\Entity\MonsterSequenceStep;
use App\Entity\MonsterTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MonsterSequenceStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonsterSequenceStep::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.stepOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFirstTemplate(): ?MonsterTemplate
    {
        $step = $this->createQueryBuilder('s')
            ->orderBy('s.stepOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $step instanceof MonsterSequenceStep ? $step->getMonsterTemplate() : null;
    }

    public function findNextTemplateAfter(?MonsterTemplate $current): ?MonsterTemplate
    {
        $steps = $this->findAllOrdered();
        if ($steps === []) {
            return null;
        }

        if (!$current instanceof MonsterTemplate || $current->getId() === null) {
            return $steps[0]->getMonsterTemplate();
        }

        $currentIndex = null;
        foreach ($steps as $index => $step) {
            if ($step->getMonsterTemplate()->getId() === $current->getId()) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return $steps[0]->getMonsterTemplate();
        }

        $nextIndex = $currentIndex + 1;
        if (!isset($steps[$nextIndex])) {
            return $steps[0]->getMonsterTemplate();
        }

        return $steps[$nextIndex]->getMonsterTemplate();
    }
}

