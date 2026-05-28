<?php

namespace App\Dto;

use App\Enum\BonusStat;
use App\Enum\Rarity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ItemDto
{
    #[Assert\NotBlank(message: 'Le nom de l\'item est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'La rareté est obligatoire.')]
    #[Assert\Choice(callback: [self::class, 'allowedRarities'], message: 'Rareté invalide.')]
    public ?string $rarity = null;

    #[Assert\NotNull(message: 'Le bonus XP est obligatoire.')]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le bonus XP doit être compris entre 0 et 100.')]
    public ?int $bonusXpPercent = 0;

    #[Assert\NotNull(message: 'Le bonus gold est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le bonus gold doit être positif ou nul.')]
    public ?int $bonusGold = 0;

    #[Assert\Choice(callback: [self::class, 'allowedBonusStats'], message: 'Stat de bonus invalide.')]
    public ?string $bonusStat = null;

    #[Assert\NotNull(message: 'La valeur du bonus de stat est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La valeur du bonus de stat doit être positive ou nulle.')]
    public ?int $bonusStatValue = 0;

    #[Assert\NotNull(message: 'Le bonus de slots d\'équipement est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le bonus de slots d\'équipement doit être positif ou nul.')]
    public ?int $bonusEquipSlots = 0;

    public ?bool $isSellable = false;

    #[Assert\Positive(message: 'Le prix boutique doit être strictement positif.')]
    public ?int $shopPrice = null;

    #[Assert\Callback]
    public function validateShopFields(ExecutionContextInterface $context): void
    {
        if ($this->isSellable && ($this->shopPrice === null || $this->shopPrice < 1)) {
            $context->buildViolation('Un prix boutique positif est requis pour un item vendable.')
                ->atPath('shopPrice')
                ->addViolation();
        }
    }

    public static function allowedRarities(): array
    {
        return Rarity::values();
    }

    public static function allowedBonusStats(): array
    {
        return [...BonusStat::values(), null];
    }
}

