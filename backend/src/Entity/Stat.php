<?php

namespace App\Entity;

use App\Enum\StatType;
use App\Repository\StatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: 'stats')]
#[ORM\UniqueConstraint(name: 'uniq_stats_user', columns: ['user_id'])]
class Stat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'La stat de force doit être positive ou nulle.')]
    private int $force = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'La stat d\'intelligence doit être positive ou nulle.')]
    private int $intelligence = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'La stat de discipline doit être positive ou nulle.')]
    private int $discipline = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'La stat de créativité doit être positive ou nulle.')]
    private int $creativity = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getForce(): int
    {
        return $this->force;
    }

    public function getIntelligence(): int
    {
        return $this->intelligence;
    }

    public function getDiscipline(): int
    {
        return $this->discipline;
    }

    public function getCreativity(): int
    {
        return $this->creativity;
    }

    public function get(StatType $stat): int
    {
        return match ($stat) {
            StatType::FORCE => $this->force,
            StatType::INTELLIGENCE => $this->intelligence,
            StatType::DISCIPLINE => $this->discipline,
            StatType::CREATIVITY => $this->creativity,
        };
    }

    public function addPoints(StatType $stat, int $points): self
    {
        if ($points < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Le nombre de points à ajouter sur %s doit être positif ou nul, %d reçu.',
                $stat->value,
                $points
            ));
        }

        if ($points === 0) {
            return $this;
        }

        match ($stat) {
            StatType::FORCE => $this->force += $points,
            StatType::INTELLIGENCE => $this->intelligence += $points,
            StatType::DISCIPLINE => $this->discipline += $points,
            StatType::CREATIVITY => $this->creativity += $points,
        };

        return $this;
    }

    public function resetValues(int $force, int $intelligence, int $discipline, int $creativity): self
    {
        foreach ([$force, $intelligence, $discipline, $creativity] as $value) {
            if ($value < 0) {
                throw new \InvalidArgumentException(sprintf(
                    'Les stats doivent être positives ou nulles, %d reçu.',
                    $value
                ));
            }
        }

        $this->force = $force;
        $this->intelligence = $intelligence;
        $this->discipline = $discipline;
        $this->creativity = $creativity;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'force' => $this->force,
            'intelligence' => $this->intelligence,
            'discipline' => $this->discipline,
            'creativity' => $this->creativity,
        ];
    }
}

