<?php

namespace App\Entity;

use App\Repository\QuestRewardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestRewardRepository::class)]
#[ORM\Table(name: 'quest_rewards')]
#[ORM\UniqueConstraint(name: 'uniq_quest_rewards_template', columns: ['quest_template_id'])]
class QuestReward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuestTemplate::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuestTemplate $questTemplate;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    private int $xp = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'La récompense gold doit être positive ou nulle.')]
    private int $gold = 0;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Item $item = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $params = null;

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

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): self
    {
        $this->xp = max(0, $xp);

        return $this;
    }

    public function getGold(): int
    {
        return $this->gold;
    }

    public function setGold(int $gold): self
    {
        $this->gold = max(0, $gold);

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): self
    {
        $this->params = $params;

        return $this;
    }
}

