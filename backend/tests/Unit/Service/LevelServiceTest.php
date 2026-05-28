<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\LevelService;
use PHPUnit\Framework\TestCase;

final class LevelServiceTest extends TestCase
{
    public function testLevelOneFromZeroXp(): void
    {
        $service = new LevelService();
        self::assertSame(1, $service->computeLevel(0));
    }

    public function testLevelOneJustBelowFirstThreshold(): void
    {
        $service = new LevelService();
        self::assertSame(1, $service->computeLevel(99));
    }

    public function testLevelTwoAtFirstThreshold(): void
    {
        $service = new LevelService();
        self::assertSame(2, $service->computeLevel(100));
    }

    public function testLevelTwoJustBelowSecondThreshold(): void
    {
        $service = new LevelService();
        self::assertSame(2, $service->computeLevel(204));
    }

    public function testLevelThreeAtSecondThreshold(): void
    {
        $service = new LevelService();
        self::assertSame(3, $service->computeLevel(205));
    }

    public function testComputeLevelDataReturnsDetailedBreakdown(): void
    {
        $service = new LevelService();
        $data = $service->computeLevelData(250);

        self::assertSame(3, $data['level']);
        self::assertSame(45, $data['xpIntoLevel']);
        self::assertSame(111, $data['xpRequiredForNextLevel']);
    }

    public function testComputeLevelClampsNegativeXpToZero(): void
    {
        $service = new LevelService();
        self::assertSame(1, $service->computeLevel(-100));
    }

    public function testCheckLevelUpDetectsSingleLevelUp(): void
    {
        $service = new LevelService();

        $user = new User();
        $user->addXp(150);

        $result = $service->checkLevelUp($user, previousXp: 50);

        self::assertSame(1, $result['oldLevel']);
        self::assertSame(2, $result['newLevel']);
        self::assertTrue($result['leveledUp']);
    }

    public function testCheckLevelUpReportsNoChangeWhenWithinSameLevel(): void
    {
        $service = new LevelService();

        $user = new User();
        $user->addXp(99);

        $result = $service->checkLevelUp($user, previousXp: 50);

        self::assertSame(1, $result['oldLevel']);
        self::assertSame(1, $result['newLevel']);
        self::assertFalse($result['leveledUp']);
    }

    public function testCheckLevelUpHandlesMultiLevelJump(): void
    {
        $service = new LevelService();

        $user = new User();
        $user->addXp(250);

        $result = $service->checkLevelUp($user, previousXp: 0);

        self::assertSame(1, $result['oldLevel']);
        self::assertSame(3, $result['newLevel']);
        self::assertTrue($result['leveledUp']);
    }

    public function testCheckLevelUpAcceptsHigherPreviousXp(): void
    {
        $service = new LevelService();

        $user = new User();
        $user->addXp(50);

        $result = $service->checkLevelUp($user, previousXp: 250);

        self::assertSame(3, $result['oldLevel']);
        self::assertSame(1, $result['newLevel']);
        self::assertFalse($result['leveledUp']);
    }
}

