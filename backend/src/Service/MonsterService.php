<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserMonster;

final class MonsterService
{
    public function __construct(
        private readonly LootService $lootService,
        private readonly UserMonsterService $userMonsterService,
        private readonly AchievementService $achievementService,
    ) {
    }

    public function onMonsterDeath(User $user, UserMonster $deadMonster): array
    {
        $deadMonster->setIsActive(false);

        $loot = $this->lootService->generateLoot(
            $user,
            $deadMonster->getTemplate(),
            flush: false
        );

        $newMonster = $this->userMonsterService->spawnNextInSequence(
            $user,
            $deadMonster->getTemplate()
        );

        $this->achievementService->checkAfterMonsterDeath($user);

        return [
            'loot' => $loot,
            'newMonster' => $newMonster,
        ];
    }
}

