<?php

namespace App\Repository;

use App\Entity\QuestReward;
use App\Entity\QuestTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestReward::class);
    }

    public function findOneByQuestTemplate(QuestTemplate $template): ?QuestReward
    {
        return $this->findOneBy(['questTemplate' => $template]);
    }
}

