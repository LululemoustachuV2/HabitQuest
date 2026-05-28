<?php

namespace App\Entity;

use App\Enum\AffinityStat;
use App\Enum\Rarity;
use App\Repository\MonsterTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MonsterTemplateRepository::class)]
#[ORM\Table(name: 'monster_templates')]
class MonsterTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du monstre est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    private string $name = '';

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Les PV de base doivent être strictement positifs.')]
    private int $baseHp = 100;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Le niveau minimum doit être au moins 1.')]
    private int $levelMin = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'Le niveau maximum doit être au moins 1.')]
    private int $levelMax = 1;

    #[ORM\Column(type: 'string', length: 20, enumType: Rarity::class)]
    private Rarity $rarity = Rarity::COMMON;

    #[ORM\Column(type: 'string', length: 20, enumType: AffinityStat::class)]
    private AffinityStat $affinityStat = AffinityStat::NEUTRAL;

    #[ORM\Column(type: 'json')]
    private array $lootTable = [];

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\Positive(message: 'Le niveau boss doit être au moins 1.')]
    private int $bossLevel = 1;

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

    public function getBaseHp(): int
    {
        return $this->baseHp;
    }

    public function setBaseHp(int $baseHp): self
    {
        $this->baseHp = max(1, $baseHp);

        return $this;
    }

    public function getLevelMin(): int
    {
        return $this->levelMin;
    }

    public function setLevelMin(int $levelMin): self
    {
        $this->levelMin = max(1, $levelMin);

        return $this;
    }

    public function getLevelMax(): int
    {
        return $this->levelMax;
    }

    public function setLevelMax(int $levelMax): self
    {
        $this->levelMax = max(1, $levelMax);

        return $this;
    }

    public function getRarity(): Rarity
    {
        return $this->rarity;
    }

    public function setRarity(Rarity $rarity): self
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getAffinityStat(): AffinityStat
    {
        return $this->affinityStat;
    }

    public function setAffinityStat(AffinityStat $affinityStat): self
    {
        $this->affinityStat = $affinityStat;

        return $this;
    }

    public function getLootTable(): array
    {
        return $this->lootTable;
    }

    public function setLootTable(array $lootTable): self
    {
        $this->lootTable = $lootTable;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getBossLevel(): int
    {
        return $this->bossLevel;
    }

    public function setBossLevel(int $bossLevel): self
    {
        $this->bossLevel = max(1, $bossLevel);

        return $this;
    }
}

