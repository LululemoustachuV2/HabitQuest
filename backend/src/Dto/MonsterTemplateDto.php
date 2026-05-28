<?php

namespace App\Dto;

use App\Enum\AffinityStat;
use App\Enum\Rarity;
use Symfony\Component\Validator\Constraints as Assert;

final class MonsterTemplateDto
{
    #[Assert\NotBlank(message: 'Le nom du monstre est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotNull(message: 'Les PV de base sont obligatoires.')]
    #[Assert\Positive(message: 'Les PV de base doivent être strictement positifs.')]
    public ?int $baseHp = null;

    #[Assert\NotNull(message: 'Le niveau minimum est obligatoire.')]
    #[Assert\Positive(message: 'Le niveau minimum doit être au moins 1.')]
    public ?int $levelMin = null;

    #[Assert\NotNull(message: 'Le niveau maximum est obligatoire.')]
    #[Assert\Positive(message: 'Le niveau maximum doit être au moins 1.')]
    public ?int $levelMax = null;

    #[Assert\NotBlank(message: 'La rareté est obligatoire.')]
    #[Assert\Choice(callback: [Rarity::class, 'values'], message: 'Rareté invalide.')]
    public ?string $rarity = null;

    #[Assert\NotBlank(message: 'La stat d\'affinité est obligatoire.')]
    #[Assert\Choice(callback: [AffinityStat::class, 'values'], message: 'Stat d\'affinité invalide.')]
    public ?string $affinityStat = null;

    #[Assert\NotNull(message: 'La table de loot est obligatoire.')]
    #[Assert\Type(type: 'array', message: 'La table de loot doit être un tableau.')]
    public ?array $lootTable = null;

    #[Assert\Length(max: 500)]
    public ?string $imageUrl = null;
}

