<?php

namespace App\Dto;

use App\Enum\AchievementCode;
use Symfony\Component\Validator\Constraints as Assert;

final class AchievementDto
{
    #[Assert\NotBlank(message: 'Le code est obligatoire.')]
    #[Assert\Choice(callback: [AchievementCode::class, 'values'], message: 'Code achievement invalide.')]
    public ?string $code = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 120)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    public ?string $description = null;
}

