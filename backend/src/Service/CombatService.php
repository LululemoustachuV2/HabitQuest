<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserMonster;

final class CombatService
{
    public function __construct(
        private readonly DamagePreviewService $damagePreviewService,
    ) {
    }

    public function calculateDamage(User $user, UserMonster $monster, int $questBaseDamage): int
    {
        return $this->damagePreviewService->calculateFinalDamage($user, $monster, $questBaseDamage);
    }

    public function dealDamage(UserMonster $monster, int $damage): array
    {
        $damage = max(0, $damage);
        $newHp = max(0, $monster->getCurrentHp() - $damage);
        $monster->setCurrentHp($newHp);

        return [
            'damage' => $damage,
            'monsterDied' => $newHp <= 0,
            'currentHp' => $newHp,
        ];
    }

    public function applyDamageFromQuestBase(User $user, UserMonster $monster, int $questBaseDamage): array
    {
        $damage = $this->calculateDamage($user, $monster, $questBaseDamage);

        return $this->dealDamage($monster, $damage);
    }
}

