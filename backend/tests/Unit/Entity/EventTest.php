<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testXpRewardCannotBeNegative(): void
    {
        $event = (new Event())->setXpReward(-100);

        self::assertSame(0, $event->getXpReward());
    }

    public function testDefaultMultipliersAreOne(): void
    {
        $event = new Event();

        self::assertSame(1.0, $event->getXpMultiplier());
        self::assertSame(1.0, $event->getGoldMultiplier());
        self::assertNull($event->getBonusRules());
    }

    public function testInvalidMultiplierFallsBackToOne(): void
    {
        $event = (new Event())
            ->setXpMultiplier(0.0)
            ->setGoldMultiplier(-2.0);

        self::assertSame(1.0, $event->getXpMultiplier());
        self::assertSame(1.0, $event->getGoldMultiplier());
    }
}

