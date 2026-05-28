<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\HabitLogRepository;
use App\Service\StreakService;
use PHPUnit\Framework\TestCase;

final class StreakServiceTest extends TestCase
{
    public function testFirstLogSetsStreakToOne(): void
    {
        $service = $this->createService();
        $user = new User();

        $service->updateOnHabitLog($user, new \DateTimeImmutable('2026-05-28 10:00:00', new \DateTimeZone('UTC')));

        self::assertSame(1, $user->getCurrentStreak());
        self::assertSame(1, $user->getLongestStreak());
        self::assertSame('2026-05-28', $user->getLastStreakDate());
    }

    public function testSecondLogSameDayDoesNotIncrement(): void
    {
        $service = $this->createService();
        $user = (new User())
            ->setCurrentStreak(1)
            ->setLongestStreak(1)
            ->setLastStreakDate('2026-05-28');

        $service->updateOnHabitLog($user, new \DateTimeImmutable('2026-05-28 10:00:00', new \DateTimeZone('UTC')));

        self::assertSame(1, $user->getCurrentStreak());
    }

    public function testLogOnConsecutiveDayIncrementsStreak(): void
    {
        $service = $this->createService();
        $user = (new User())
            ->setCurrentStreak(3)
            ->setLongestStreak(3)
            ->setLastStreakDate('2026-05-27');

        $service->updateOnHabitLog($user, new \DateTimeImmutable('2026-05-28 08:00:00', new \DateTimeZone('UTC')));

        self::assertSame(4, $user->getCurrentStreak());
        self::assertSame(4, $user->getLongestStreak());
    }

    public function testGapResetsStreakToOne(): void
    {
        $service = $this->createService();
        $user = (new User())
            ->setCurrentStreak(5)
            ->setLongestStreak(5)
            ->setLastStreakDate('2026-05-25');

        $service->updateOnHabitLog($user, new \DateTimeImmutable('2026-05-28 08:00:00', new \DateTimeZone('UTC')));

        self::assertSame(1, $user->getCurrentStreak());
        self::assertSame(5, $user->getLongestStreak());
    }

    public function testXpBonusPercentIsCappedAtFifty(): void
    {
        $service = $this->createService();
        $user = (new User())->setCurrentStreak(15);

        self::assertSame(50, $service->getXpBonusPercent($user));
    }

    public function testXpBonusPercentScalesFivePercentPerDay(): void
    {
        $service = $this->createService();
        $user = (new User())->setCurrentStreak(4);

        self::assertSame(20, $service->getXpBonusPercent($user));
    }

    private function createService(): StreakService
    {
        $repository = $this->createMock(HabitLogRepository::class);

        return new StreakService($repository);
    }
}

