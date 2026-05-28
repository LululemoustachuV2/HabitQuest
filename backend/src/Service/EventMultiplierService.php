<?php

namespace App\Service;

use App\Repository\EventRepository;

final class EventMultiplierService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function resolveActiveMultipliers(?\DateTimeImmutable $at = null): array
    {
        $at ??= new \DateTimeImmutable();
        $xp = 1.0;
        $gold = 1.0;

        foreach ($this->eventRepository->findActiveAt($at) as $event) {
            $xp *= $event->getXpMultiplier();
            $gold *= $event->getGoldMultiplier();
        }

        return ['xp' => $xp, 'gold' => $gold];
    }

    public function applyXp(int $amount, ?\DateTimeImmutable $at = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $multiplier = $this->resolveActiveMultipliers($at)['xp'];
        if ($multiplier === 1.0) {
            return $amount;
        }

        return max(0, (int) floor($amount * $multiplier));
    }

    public function applyGold(int $amount, ?\DateTimeImmutable $at = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $multiplier = $this->resolveActiveMultipliers($at)['gold'];
        if ($multiplier === 1.0) {
            return $amount;
        }

        return max(0, (int) floor($amount * $multiplier));
    }

    public function resolveDamageMultiplier(?\DateTimeImmutable $at = null): float
    {
        $at ??= new \DateTimeImmutable();
        $damage = 1.0;

        foreach ($this->eventRepository->findActiveAt($at) as $event) {
            $rules = $event->getBonusRules();
            if (!\is_array($rules)) {
                continue;
            }

            $factor = $rules['damageMultiplier'] ?? $rules['damage_multiplier'] ?? null;
            if ($factor === null) {
                continue;
            }

            $parsed = (float) $factor;
            if ($parsed > 0) {
                $damage *= $parsed;
            }
        }

        return $damage;
    }
}

