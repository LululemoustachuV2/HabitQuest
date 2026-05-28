<?php

namespace App\Entity;

use App\Enum\BonusStat;
use App\Enum\Rarity;
use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'item est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private string $description = '';

    #[ORM\Column(type: 'string', length: 20, enumType: Rarity::class)]
    private Rarity $rarity = Rarity::COMMON;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le bonus XP doit être compris entre 0 et 100.')]
    private int $bonusXpPercent = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Le bonus gold doit être positif ou nul.')]
    private int $bonusGold = 0;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: BonusStat::class)]
    private ?BonusStat $bonusStat = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'La valeur du bonus de stat doit être positive ou nulle.')]
    private int $bonusStatValue = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Le bonus de slots d\'équipement doit être positif ou nul.')]
    private int $bonusEquipSlots = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Le bonus de dégâts doit être positif ou nul.')]
    private int $bonusDamage = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le bonus de dégâts en % doit être entre 0 et 100.')]
    private int $bonusDamagePercent = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSellable = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $shopPrice = null;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getBonusXpPercent(): int
    {
        return $this->bonusXpPercent;
    }

    public function setBonusXpPercent(int $bonusXpPercent): self
    {
        $this->bonusXpPercent = max(0, min(100, $bonusXpPercent));

        return $this;
    }

    public function getBonusGold(): int
    {
        return $this->bonusGold;
    }

    public function setBonusGold(int $bonusGold): self
    {
        $this->bonusGold = max(0, $bonusGold);

        return $this;
    }

    public function getBonusStat(): ?BonusStat
    {
        return $this->bonusStat;
    }

    public function setBonusStat(?BonusStat $bonusStat): self
    {
        $this->bonusStat = $bonusStat;

        return $this;
    }

    public function getBonusStatValue(): int
    {
        return $this->bonusStatValue;
    }

    public function setBonusStatValue(int $bonusStatValue): self
    {
        $this->bonusStatValue = max(0, $bonusStatValue);

        return $this;
    }

    public function getBonusEquipSlots(): int
    {
        return $this->bonusEquipSlots;
    }

    public function setBonusEquipSlots(int $bonusEquipSlots): self
    {
        $this->bonusEquipSlots = max(0, $bonusEquipSlots);

        return $this;
    }

    public function getBonusDamage(): int
    {
        return $this->bonusDamage;
    }

    public function setBonusDamage(int $bonusDamage): self
    {
        $this->bonusDamage = max(0, $bonusDamage);

        return $this;
    }

    public function getBonusDamagePercent(): int
    {
        return $this->bonusDamagePercent;
    }

    public function setBonusDamagePercent(int $bonusDamagePercent): self
    {
        $this->bonusDamagePercent = max(0, min(100, $bonusDamagePercent));

        return $this;
    }

    public function isSellable(): bool
    {
        return $this->isSellable;
    }

    public function setIsSellable(bool $isSellable): self
    {
        $this->isSellable = $isSellable;
        if (!$isSellable) {
            $this->shopPrice = null;
        }

        return $this;
    }

    public function getShopPrice(): ?int
    {
        return $this->shopPrice;
    }

    public function setShopPrice(?int $shopPrice): self
    {
        $this->shopPrice = $shopPrice !== null ? max(1, $shopPrice) : null;

        return $this;
    }
}

