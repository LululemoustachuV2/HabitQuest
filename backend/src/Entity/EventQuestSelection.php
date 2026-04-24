<?php

namespace App\Entity;

use App\Repository\EventQuestSelectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventQuestSelectionRepository::class)]
#[ORM\Table(name: 'event_quest_selections')]
class EventQuestSelection
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: QuestTemplate::class)]
    #[ORM\JoinColumn(name: 'quest_template_id', nullable: false)]
    private QuestTemplate $questTemplate;

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getQuestTemplate(): QuestTemplate
    {
        return $this->questTemplate;
    }

    public function setQuestTemplate(QuestTemplate $questTemplate): self
    {
        $this->questTemplate = $questTemplate;

        return $this;
    }
}
