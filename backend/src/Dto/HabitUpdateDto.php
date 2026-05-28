<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class HabitUpdateDto
{
    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;

    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $description = null;

    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $xpReward = null;

    #[Assert\PositiveOrZero(message: 'La récompense gold doit être positive ou nulle.')]
    public ?int $goldReward = null;

    #[Assert\Positive(message: 'L\'identifiant de catégorie doit être strictement positif.')]
    public ?int $categoryId = null;

    public ?bool $isActive = null;
}

