<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class QuestRewardDto
{
    #[Assert\NotNull(message: 'La récompense XP est obligatoire.')]
    #[Assert\Type(type: 'integer', message: 'La récompense XP doit être un entier.')]
    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $xp = null;

    #[Assert\NotNull(message: 'La récompense gold est obligatoire.')]
    #[Assert\Type(type: 'integer', message: 'La récompense gold doit être un entier.')]
    #[Assert\PositiveOrZero(message: 'La récompense gold doit être positive ou nulle.')]
    public ?int $gold = null;

    #[Assert\Type(type: 'integer', message: "L'identifiant d'item doit être un entier.")]
    #[Assert\Positive(message: "L'identifiant d'item doit être strictement positif.")]
    public ?int $itemId = null;

    #[Assert\Type(type: 'array', message: 'Les paramètres doivent être un objet JSON.')]
    public ?array $params = null;
}

