<?php

namespace App\Entity;

use App\Repository\MonsterSequenceStepRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonsterSequenceStepRepository::class)]
#[ORM\Table(name: 'monster_sequence_steps')]
#[ORM\UniqueConstraint(name: 'uniq_monster_sequence_step_order', columns: ['step_order'])]
class MonsterSequenceStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'step_order', type: 'integer')]
    private int $stepOrder = 0;

    #[ORM\ManyToOne(targetEntity: MonsterTemplate::class)]
    #[ORM\JoinColumn(name: 'monster_template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private MonsterTemplate $monsterTemplate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStepOrder(): int
    {
        return $this->stepOrder;
    }

    public function setStepOrder(int $stepOrder): self
    {
        $this->stepOrder = $stepOrder;

        return $this;
    }

    public function getMonsterTemplate(): MonsterTemplate
    {
        return $this->monsterTemplate;
    }

    public function setMonsterTemplate(MonsterTemplate $monsterTemplate): self
    {
        $this->monsterTemplate = $monsterTemplate;

        return $this;
    }
}

