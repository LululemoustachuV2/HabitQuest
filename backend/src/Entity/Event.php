<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_user_id', nullable: false)]
    private User $createdBy;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    #[Assert\GreaterThan(propertyPath: 'startsAt', message: 'La date de fin doit être postérieure à la date de début.')]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $xpReward = 0;

    #[ORM\Column(type: 'float', options: ['default' => 1.0])]
    #[Assert\Positive]
    private float $xpMultiplier = 1.0;

    #[ORM\Column(type: 'float', options: ['default' => 1.0])]
    #[Assert\Positive]
    private float $goldMultiplier = 1.0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bonusRules = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getXpReward(): int
    {
        return $this->xpReward;
    }

    public function setXpReward(int $xpReward): self
    {
        $this->xpReward = max(0, $xpReward);

        return $this;
    }

    public function getXpMultiplier(): float
    {
        return $this->xpMultiplier;
    }

    public function setXpMultiplier(float $xpMultiplier): self
    {
        $this->xpMultiplier = $xpMultiplier > 0 ? $xpMultiplier : 1.0;

        return $this;
    }

    public function getGoldMultiplier(): float
    {
        return $this->goldMultiplier;
    }

    public function setGoldMultiplier(float $goldMultiplier): self
    {
        $this->goldMultiplier = $goldMultiplier > 0 ? $goldMultiplier : 1.0;

        return $this;
    }

    public function getBonusRules(): ?array
    {
        return $this->bonusRules;
    }

    public function setBonusRules(?array $bonusRules): self
    {
        $this->bonusRules = $bonusRules;

        return $this;
    }
}

