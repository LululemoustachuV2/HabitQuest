<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateEventDto
{
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})$/',
        message: 'La date de début doit être au format ISO 8601.'
    )]
    public ?string $startsAt = null;

    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})$/',
        message: 'La date de fin doit être au format ISO 8601.'
    )]
    public ?string $endsAt = null;

    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $eventXpReward = null;

    #[Assert\Positive(message: 'Le multiplicateur XP doit être strictement positif.')]
    public ?float $xpMultiplier = null;

    #[Assert\Positive(message: 'Le multiplicateur gold doit être strictement positif.')]
    public ?float $goldMultiplier = null;

    #[Assert\Type('array')]
    public ?array $bonusRules = null;
}

