<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class HabitDto
{
    #[Assert\NotBlank(message: 'Le nom de l\'habitude est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;

    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $description = '';

    #[Assert\NotNull(message: 'La récompense XP est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $xpReward = 0;

    #[Assert\NotNull(message: 'La récompense gold est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La récompense gold doit être positive ou nulle.')]
    public ?int $goldReward = 0;

    #[Assert\Positive(message: 'L\'identifiant de catégorie doit être strictement positif.')]
    public ?int $categoryId = null;

    public ?bool $isActive = true;
}

