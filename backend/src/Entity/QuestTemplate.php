<?php

namespace App\Entity;

use App\Enum\QuestKind;
use App\Repository\QuestTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestTemplateRepository::class)]
#[ORM\Table(name: 'quest_templates')]
class QuestTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, enumType: QuestKind::class)]
    private QuestKind $kind = QuestKind::DAILY;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private string $description = '';

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'La récompense doit être positive ou nulle.')]
    private int $xpReward = 0;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Le niveau requis doit être strictement positif.')]
    private int $requiredLevel = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKind(): QuestKind
    {
        return $this->kind;
    }

    public function setKind(QuestKind $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = max(1, $requiredLevel);

        return $this;
    }
}
