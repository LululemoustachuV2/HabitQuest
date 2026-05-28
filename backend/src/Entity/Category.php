<?php

namespace App\Entity;

use App\Enum\StatType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'uniq_categories_name', columns: ['name'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 20, enumType: StatType::class)]
    private StatType $linkedStat = StatType::DISCIPLINE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLinkedStat(): StatType
    {
        return $this->linkedStat;
    }

    public function setLinkedStat(StatType $linkedStat): self
    {
        $this->linkedStat = $linkedStat;

        return $this;
    }
}

