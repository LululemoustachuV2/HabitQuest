<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventDto
{
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})$/',
        message: 'La date de début doit être au format ISO 8601.'
    )]
    public ?string $startsAt = null;

    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})$/',
        message: 'La date de fin doit être au format ISO 8601.'
    )]
    public ?string $endsAt = null;

    #[Assert\NotBlank(message: 'Au moins une quête doit être associée à un événement.')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'Au moins une quête doit être associée à un événement.')]
    public array $questTemplateIds = [];

    #[Assert\NotNull(message: 'La récompense XP de l\'événement est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $eventXpReward = null;
}
