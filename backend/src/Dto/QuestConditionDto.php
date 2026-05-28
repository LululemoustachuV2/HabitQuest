<?php

namespace App\Dto;

use App\Enum\QuestConditionKind;
use Symfony\Component\Validator\Constraints as Assert;

final class QuestConditionDto
{
    #[Assert\NotBlank(message: 'Le type de condition est obligatoire.')]
    #[Assert\Choice(callback: [QuestConditionKind::class, 'values'], message: 'Type de condition invalide.')]
    public ?string $kind = null;

    #[Assert\NotNull(message: 'Les paramètres sont obligatoires.')]
    #[Assert\Type(type: 'array', message: 'Les paramètres doivent être un objet JSON.')]
    public ?array $params = null;
}

