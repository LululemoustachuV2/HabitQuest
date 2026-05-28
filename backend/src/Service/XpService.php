<?php

namespace App\Service;

use App\Entity\Habit;
use App\Entity\User;
use App\Repository\InventoryRepository;

class XpService
{
    public function __construct(
        private readonly InventoryRepository $inventoryRepository,
        private readonly StreakService $streakService,
    ) {
    }

    public function calculate(Habit $habit, User $user): int
    {
        $xpBase = max(0, $habit->getXpReward());
        if ($xpBase === 0) {
            return 0;
        }

        $bonusXpPercent = $this->computeEquippedBonusXpPercent($user);
        $afterItems = (int) floor($xpBase * (1 + ($bonusXpPercent / 100)));

        $streakBonusPercent = $this->computeStreakBonusXpPercent($user);
        $xpFinal = (int) floor($afterItems * (1 + ($streakBonusPercent / 100)));

        return max(0, $xpFinal);
    }

    protected function computeEquippedBonusXpPercent(User $user): int
    {
        $sum = 0;
        foreach ($this->inventoryRepository->findEquippedForUser($user) as $entry) {
            $sum += $entry->getItem()->getBonusXpPercent();
        }

        return max(0, $sum);
    }

    protected function computeStreakBonusXpPercent(User $user): int
    {
        return $this->streakService->getXpBonusPercent($user);
    }
}

