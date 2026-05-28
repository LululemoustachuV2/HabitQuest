<?php

namespace App\Entity;

use App\Enum\QuestConditionKind;
use App\Repository\QuestConditionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestConditionRepository::class)]
#[ORM\Table(name: 'quest_conditions')]
class QuestCondition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuestTemplate::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuestTemplate $questTemplate;

    #[ORM\Column(type: 'string', length: 40, enumType: QuestConditionKind::class)]
    private QuestConditionKind $kind;

    #[ORM\Column(type: 'json')]
    private array $params = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getKind(): QuestConditionKind
    {
        return $this->kind;
    }

    public function setKind(QuestConditionKind $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }
}

