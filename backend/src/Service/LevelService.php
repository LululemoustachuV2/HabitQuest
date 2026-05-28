<?php

namespace App\Service;

use App\Entity\User;

final class LevelService
{
    public const FIRST_LEVEL_XP_COST = 100;

    public const LEVEL_XP_MULTIPLIER = 1.05;

    public function computeLevel(int $totalXp): int
    {
        return $this->computeLevelData($totalXp)['level'];
    }

    public function computeLevelData(int $totalXp): array
    {
        $remainingXp = max(0, $totalXp);
        $level = 1;
        $xpRequiredForLevel = self::FIRST_LEVEL_XP_COST;

        while ($remainingXp >= $xpRequiredForLevel) {
            $remainingXp -= $xpRequiredForLevel;
            ++$level;
            $xpRequiredForLevel = (int) ceil($xpRequiredForLevel * self::LEVEL_XP_MULTIPLIER);
        }

        return [
            'level' => $level,
            'xpIntoLevel' => $remainingXp,
            'xpRequiredForNextLevel' => $xpRequiredForLevel,
        ];
    }

    public function checkLevelUp(User $user, int $previousXp): array
    {
        $currentXp = $user->getXp();
        $oldLevel = $this->computeLevel($previousXp);
        $newLevel = $this->computeLevel($currentXp);

        return [
            'oldLevel' => $oldLevel,
            'newLevel' => $newLevel,
            'leveledUp' => $newLevel > $oldLevel,
        ];
    }
}

