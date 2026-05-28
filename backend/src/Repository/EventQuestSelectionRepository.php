<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventQuestSelection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventQuestSelectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventQuestSelection::class);
    }

    public function findByEvent(Event $event): array
    {
        return $this->findBy(['event' => $event]);
    }
}

