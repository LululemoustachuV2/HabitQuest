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
}
