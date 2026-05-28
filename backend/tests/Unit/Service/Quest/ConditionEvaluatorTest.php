<?php

namespace App\Tests\Unit\Service\Quest;

use App\Entity\Category;
use App\Entity\Habit;
use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\StatType;
use App\Service\Quest\Evaluator\CategoryLogsCountEvaluator;
use App\Service\Quest\Evaluator\GoldGainedEvaluator;
use App\Service\Quest\Evaluator\HabitLogsCountEvaluator;
use App\Service\Quest\Evaluator\StreakDaysEvaluator;
use App\Service\Quest\Evaluator\XpGainedEvaluator;
use App\Service\StreakService;
use PHPUnit\Framework\TestCase;

final class ConditionEvaluatorTest extends TestCase
{
    public function testHabitLogsCountTargetAndIncrement(): void
    {
        $evaluator = new HabitLogsCountEvaluator();
        $user = new User();
        $habitA = (new Habit())->setUser($user)->setName('A');
        $habitB = (new Habit())->setUser($user)->setName('B');

        self::assertSame(5, $evaluator->getTarget(['count' => 5]));
        self::assertTrue($evaluator->appliesToLog(['count' => 5], new HabitLog($habitA, $user, 10, 0)));
        self::assertFalse($evaluator->appliesToLog(['count' => 5, 'habitId' => 99], new HabitLog($habitA, $user, 10, 0)));
        self::assertSame(1, $evaluator->incrementForLog(['count' => 5], new HabitLog($habitB, $user, 0, 0)));
        self::assertNull($evaluator->recomputeCurrent(['count' => 5], $user, new \DateTimeImmutable()));
    }

    public function testCategoryLogsCountFiltersByCategory(): void
    {
        $evaluator = new CategoryLogsCountEvaluator();
        $user = new User();
        $category = (new Category())->setName('Sport')->setLinkedStat(StatType::FORCE);
        $idProperty = new \ReflectionProperty(Category::class, 'id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($category, 1);
        $habit = (new Habit())->setUser($user)->setName('Run')->setCategory($category);
        $log = new HabitLog($habit, $user, 10, 0);

        self::assertSame(3, $evaluator->getTarget(['count' => 3, 'categoryId' => 1]));
        self::assertTrue($evaluator->appliesToLog(['count' => 3, 'categoryId' => 1], $log));
        self::assertFalse($evaluator->appliesToLog(['count' => 3, 'categoryId' => 2], $log));
    }

    public function testXpGainedUsesLogAmount(): void
    {
        $evaluator = new XpGainedEvaluator();
        $user = new User();
        $habit = (new Habit())->setUser($user)->setName('Read');
        $log = new HabitLog($habit, $user, 42, 0);

        self::assertSame(100, $evaluator->getTarget(['amount' => 100]));
        self::assertTrue($evaluator->appliesToLog(['amount' => 100], $log));
        self::assertSame(42, $evaluator->incrementForLog(['amount' => 100], $log));
        self::assertFalse($evaluator->appliesToLog(['amount' => 100], new HabitLog($habit, $user, 0, 5)));
    }

    public function testGoldGainedUsesLogAmount(): void
    {
        $evaluator = new GoldGainedEvaluator();
        $user = new User();
        $habit = (new Habit())->setUser($user)->setName('Save');
        $log = new HabitLog($habit, $user, 0, 15);

        self::assertSame(50, $evaluator->getTarget(['amount' => 50]));
        self::assertSame(15, $evaluator->incrementForLog(['amount' => 50], $log));
    }

    public function testStreakDaysRecomputesFromUserStreak(): void
    {
        $streakService = $this->createMock(StreakService::class);
        $streakService->method('getHabitStreakDays')->willReturn(3);

        $evaluator = new StreakDaysEvaluator($streakService);
        $user = (new User())->setCurrentStreak(4);
        $since = new \DateTimeImmutable('-7 days');

        self::assertSame(7, $evaluator->getTarget(['days' => 7]));
        self::assertSame(0, $evaluator->incrementForLog(['days' => 7], new HabitLog(
            (new Habit())->setUser($user)->setName('H'),
            $user,
            1,
            0
        )));
        self::assertSame(4, $evaluator->recomputeCurrent(['days' => 7], $user, $since));
        self::assertSame(3, $evaluator->recomputeCurrent(['days' => 7, 'habitId' => 12], $user, $since));
    }
}

