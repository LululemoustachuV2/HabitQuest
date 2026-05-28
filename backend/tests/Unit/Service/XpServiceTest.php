<?php

namespace App\Tests\Unit\Service;

use App\Entity\Habit;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Repository\InventoryRepository;
use App\Service\StreakService;
use App\Service\XpService;
use PHPUnit\Framework\TestCase;

final class XpServiceTest extends TestCase
{
    public function testCalculateReturnsBaseXpRewardWhenNoBonus(): void
    {
        $service = $this->createServiceWithEquipped([]);

        $habit = $this->makeHabit(xpReward: 25);
        $user = new User();

        self::assertSame(25, $service->calculate($habit, $user));
    }

    public function testCalculateReturnsZeroWhenHabitHasNoXpReward(): void
    {
        $service = $this->createServiceWithEquipped([]);

        $habit = $this->makeHabit(xpReward: 0);
        $user = new User();

        self::assertSame(0, $service->calculate($habit, $user));
    }

    public function testCalculateNeverReturnsNegative(): void
    {
        $service = $this->createServiceWithEquipped([]);

        $habit = $this->makeHabit(xpReward: 0);
        $user = new User();

        self::assertGreaterThanOrEqual(0, $service->calculate($habit, $user));
    }

    public function testCalculateSumsEquippedBonusXpPercent(): void
    {
        $itemA = (new Item())->setBonusXpPercent(10);
        $itemB = (new Item())->setBonusXpPercent(5);
        $user = new User();

        $invA = (new Inventory())->setUser($user)->setItem($itemA)->setIsEquipped(true);
        $invB = (new Inventory())->setUser($user)->setItem($itemB)->setIsEquipped(true);

        $service = $this->createServiceWithEquipped([$invA, $invB]);
        $habit = $this->makeHabit(xpReward: 80);

        self::assertSame(92, $service->calculate($habit, $user));
    }

    public function testCalculateAppliesBonusXpPercentWhenSubclassed(): void
    {
        $streakService = $this->createMock(StreakService::class);
        $streakService->method('getXpBonusPercent')->willReturn(0);

        $service = new class(
            $this->createMock(InventoryRepository::class),
            $streakService,
        ) extends XpService {
            protected function computeEquippedBonusXpPercent(User $user): int
            {
                unset($user);

                return 50;
            }
        };

        $habit = $this->makeHabit(xpReward: 80);
        $user = new User();

        self::assertSame(120, $service->calculate($habit, $user));
    }

    public function testCalculateFloorsFractionalBonus(): void
    {
        $item = (new Item())->setBonusXpPercent(33);
        $user = new User();
        $inv = (new Inventory())->setUser($user)->setItem($item)->setIsEquipped(true);

        $service = $this->createServiceWithEquipped([$inv]);
        $habit = $this->makeHabit(xpReward: 50);

        self::assertSame(66, $service->calculate($habit, $user));
    }

    public function testCalculateAppliesStreakBonusAfterItems(): void
    {
        $user = (new User())->setCurrentStreak(4);
        $service = $this->createServiceWithEquipped([], streakBonusPercent: 20);
        $habit = $this->makeHabit(xpReward: 100);

        self::assertSame(120, $service->calculate($habit, $user));
    }

    public function testCalculateStreakBonusIsCappedAtFiftyPercent(): void
    {
        $user = (new User())->setCurrentStreak(99);
        $service = $this->createServiceWithEquipped([], streakBonusPercent: 50);
        $habit = $this->makeHabit(xpReward: 100);

        self::assertSame(150, $service->calculate($habit, $user));
    }

    private function createServiceWithEquipped(array $equipped, int $streakBonusPercent = 0): XpService
    {
        $repository = $this->createMock(InventoryRepository::class);
        $repository
            ->method('findEquippedForUser')
            ->willReturn($equipped);

        $streakService = $this->createMock(StreakService::class);
        $streakService
            ->method('getXpBonusPercent')
            ->willReturn($streakBonusPercent);

        return new XpService($repository, $streakService);
    }

    private function makeHabit(int $xpReward): Habit
    {
        $habit = new Habit();
        $habit->setName('test-habit');
        $habit->setDescription('');
        $habit->setXpReward($xpReward);
        $habit->setGoldReward(0);
        $habit->setIsActive(true);
        $habit->setUser(new User());

        return $habit;
    }
}

