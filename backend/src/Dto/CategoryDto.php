<?php

namespace App\Dto;

use App\Enum\StatType;
use Symfony\Component\Validator\Constraints as Assert;

final class CategoryDto
{
    #[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'La stat liée est obligatoire.')]
    #[Assert\Choice(callback: [self::class, 'allowedStats'], message: 'Stat liée invalide.')]
    public ?string $linkedStat = null;

    public static function allowedStats(): array
    {
        return StatType::values();
    }
}

