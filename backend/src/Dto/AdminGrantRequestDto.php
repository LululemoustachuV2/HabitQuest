<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminGrantRequestDto
{
    #[Assert\NotNull(message: 'Le montant est obligatoire.')]
    #[Assert\Positive(message: 'Le montant doit être strictement positif.')]
    public ?int $amount = null;

    #[Assert\NotBlank(message: 'La raison est obligatoire.')]
    #[Assert\Length(min: 3, max: 500, minMessage: 'La raison doit contenir au moins {{ limit }} caractères.')]
    public ?string $reason = null;
}

