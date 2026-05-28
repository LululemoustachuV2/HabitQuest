<?php

namespace App\Tests\Unit\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\EventMultiplierService;
use PHPUnit\Framework\TestCase;

final class EventMultiplierServiceTest extends TestCase
{
    public function testReturnsOneWhenNoActiveEvent(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->method('findActiveAt')->willReturn([]);

        $service = new EventMultiplierService($repo);

        self::assertSame(['xp' => 1.0, 'gold' => 1.0], $service->resolveActiveMultipliers());
        self::assertSame(25, $service->applyXp(25));
        self::assertSame(10, $service->applyGold(10));
    }

    public function testStacksMultipliersFromOverlappingEvents(): void
    {
        $eventA = (new Event())
            ->setXpMultiplier(2.0)
            ->setGoldMultiplier(1.5);
        $eventB = (new Event())
            ->setXpMultiplier(1.5)
            ->setGoldMultiplier(2.0);

        $repo = $this->createMock(EventRepository::class);
        $repo->method('findActiveAt')->willReturn([$eventA, $eventB]);

        $service = new EventMultiplierService($repo);

        self::assertSame(['xp' => 3.0, 'gold' => 3.0], $service->resolveActiveMultipliers());
        self::assertSame(30, $service->applyXp(10));
        self::assertSame(15, $service->applyGold(5));
    }

    public function testApplyXpReturnsZeroForZeroInput(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->method('findActiveAt')->willReturn([
            (new Event())->setXpMultiplier(5.0),
        ]);

        $service = new EventMultiplierService($repo);

        self::assertSame(0, $service->applyXp(0));
    }
}

