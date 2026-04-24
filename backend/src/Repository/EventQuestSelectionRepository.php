<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventQuestSelection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventQuestSelection>
 */
class EventQuestSelectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventQuestSelection::class);
    }

    /**
     * @return EventQuestSelection[]
     */
    public function findByEvent(Event $event): array
    {
        return $this->findBy(['event' => $event]);
    }
}
