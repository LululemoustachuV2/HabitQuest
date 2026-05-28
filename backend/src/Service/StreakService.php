<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\HabitLogRepository;

class StreakService
{
    public const TIMEZONE = 'Europe/Paris';

    public const PER_DAY_BONUS_PERCENT = 5;

    public const MAX_BONUS_PERCENT = 50;

    private readonly \DateTimeZone $parisTz;

    public function __construct(
        private readonly HabitLogRepository $habitLogRepository,
    ) {
        $this->parisTz = new \DateTimeZone(self::TIMEZONE);
    }

    public function updateOnHabitLog(User $user, ?\DateTimeImmutable $loggedAt = null): void
    {
        $logDate = $this->toCalendarDateKey($loggedAt ?? new \DateTimeImmutable());
        $lastDate = $user->getLastStreakDate();

        if ($lastDate === $logDate) {
            return;
        }

        if ($lastDate === null) {
            $user->setCurrentStreak(1);
        } elseif ($lastDate === $this->previousCalendarDateKey($logDate)) {
            $user->setCurrentStreak($user->getCurrentStreak() + 1);
        } else {
            $user->setCurrentStreak(1);
        }

        $user->setLastStreakDate($logDate);

        if ($user->getCurrentStreak() > $user->getLongestStreak()) {
            $user->setLongestStreak($user->getCurrentStreak());
        }
    }

    public function getXpBonusPercent(User $user): int
    {
        return min(self::MAX_BONUS_PERCENT, max(0, $user->getCurrentStreak()) * self::PER_DAY_BONUS_PERCENT);
    }

    public function getHabitStreakDays(User $user, int $habitId): int
    {
        return $this->habitLogRepository->computeConsecutiveStreakDaysForHabit($user, $habitId);
    }

    public function toCalendarDateKey(\DateTimeImmutable $instant): string
    {
        return $instant->setTimezone($this->parisTz)->format('Y-m-d');
    }

    private function previousCalendarDateKey(string $dateKey): string
    {
        $date = new \DateTimeImmutable($dateKey.' 00:00:00', $this->parisTz);

        return $date->modify('-1 day')->format('Y-m-d');
    }
}

