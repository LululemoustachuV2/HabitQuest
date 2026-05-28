<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserMonster;
use App\Enum\AffinityStat;
use App\Enum\StatType;
use App\Repository\InventoryRepository;

final class DamagePreviewService
{
    public function __construct(
        private readonly StatService $statService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly EventMultiplierService $eventMultiplierService,
        private readonly LevelService $levelService,
    ) {
    }

    public function preview(User $user, UserMonster $monster, int $questBaseDamage): array
    {
        $breakdown = $this->buildBreakdown($user, $monster, max(0, $questBaseDamage));

        return [
            'estimatedDamage' => $this->finalDamageFromBreakdown($breakdown),
            'breakdown' => $breakdown,
        ];
    }

    public function calculateFinalDamage(User $user, UserMonster $monster, int $questBaseDamage): int
    {
        return $this->finalDamageFromBreakdown(
            $this->buildBreakdown($user, $monster, max(0, $questBaseDamage))
        );
    }

    private function finalDamageFromBreakdown(array $breakdown): int
    {
        $raw = ($breakdown['questBase'] + $breakdown['statPower'] + $breakdown['itemFlat'])
            * (1 + ($breakdown['itemPercent'] / 100.0))
            * $breakdown['eventMult'];
        $raw = max(0, (int) floor($raw));

        if ($raw <= 0) {
            return 0;
        }

        return max(1, (int) floor($raw * $breakdown['levelMult']));
    }

    private function buildBreakdown(User $user, UserMonster $monster, int $questBaseDamage): array
    {
        $affinity = $monster->getTemplate()->getAffinityStat();
        $stat = $this->statService->getOrCreateForUser($user);
        $itemBonuses = $this->sumEquippedDamageBonuses($user);

        $playerLevel = $this->levelService->computeLevel($user->getXp());
        $bossLevel = $monster->getTemplate()->getBossLevel();
        $diff = $playerLevel - $bossLevel;

        return [
            'questBase' => $questBaseDamage,
            'statPower' => $this->computeStatPower($stat, $affinity),
            'itemFlat' => $itemBonuses['flat'],
            'itemPercent' => $itemBonuses['percent'],
            'eventMult' => $this->eventMultiplierService->resolveDamageMultiplier(),
            'levelMult' => $this->clampLevelMultiplier(1.0 + $diff * 0.10),
            'playerLevel' => $playerLevel,
            'bossLevel' => $bossLevel,
        ];
    }

    private function computeStatPower(\App\Entity\Stat $stat, AffinityStat $affinity): int
    {
        $force = $stat->get(StatType::FORCE);
        $intelligence = $stat->get(StatType::INTELLIGENCE);
        $discipline = $stat->get(StatType::DISCIPLINE);
        $creativity = $stat->get(StatType::CREATIVITY);

        if ($affinity === AffinityStat::NEUTRAL) {
            return (int) floor(($force + $intelligence + $discipline + $creativity) / 4);
        }

        $primary = $stat->get(StatType::from($affinity->value));
        $secondaryValues = match ($affinity) {
            AffinityStat::FORCE => [$intelligence, $discipline, $creativity],
            AffinityStat::INTELLIGENCE => [$force, $discipline, $creativity],
            AffinityStat::DISCIPLINE => [$force, $intelligence, $creativity],
            AffinityStat::CREATIVITY => [$force, $intelligence, $discipline],
            AffinityStat::NEUTRAL => [],
        };
        $secondary = (int) floor(array_sum($secondaryValues) / 3);

        return (int) floor($primary * 1.25 + $secondary * 0.35);
    }

    private function sumEquippedDamageBonuses(User $user): array
    {
        $flat = 0;
        $percent = 0;

        foreach ($this->inventoryRepository->findEquippedForUser($user) as $entry) {
            $item = $entry->getItem();
            $flat += $item->getBonusDamage();
            $percent += $item->getBonusDamagePercent();
        }

        return ['flat' => max(0, $flat), 'percent' => max(0, $percent)];
    }

    private function clampLevelMultiplier(float $value): float
    {
        return max(0.45, min(1.60, $value));
    }
}

