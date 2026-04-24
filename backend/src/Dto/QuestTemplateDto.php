<?php

namespace App\Dto;

use App\Enum\QuestKind;
use Symfony\Component\Validator\Constraints as Assert;

final class QuestTemplateDto
{
    #[Assert\NotBlank(message: 'Le type de quête est obligatoire.')]
    #[Assert\Choice(callback: [self::class, 'allowedKinds'], message: 'Type de quête invalide.')]
    public ?string $kind = null;

    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    public ?string $description = null;

    #[Assert\NotNull(message: 'La récompense XP est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La récompense XP doit être positive ou nulle.')]
    public ?int $xpReward = null;

    #[Assert\Positive(message: 'Le niveau requis doit être strictement positif.')]
    public int $requiredLevel = 1;

    public bool $isActive = true;

    /**
     * @return string[]
     */
    public static function allowedKinds(): array
    {
        return array_map(static fn (QuestKind $kind): string => $kind->value, QuestKind::cases());
    }
}
