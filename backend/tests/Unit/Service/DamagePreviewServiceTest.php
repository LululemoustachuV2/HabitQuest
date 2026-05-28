<?php

namespace App\Tests\Unit\Service;

use App\Entity\MonsterTemplate;
use App\Entity\Stat;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Enum\AffinityStat;
use App\Enum\Rarity;
use App\Enum\StatType;
use App\Repository\EventRepository;
use App\Repository\InventoryRepository;
use App\Repository\StatRepository;
use App\Service\DamagePreviewService;
use App\Service\EventMultiplierService;
use App\Service\LevelService;
use App\Service\StatService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DamagePreviewServiceTest extends TestCase
{
    public function testNeutralStatPowerIsAverageOfFourStats(): void
    {
        $service = $this->createService(statForce: 8, statInt: 12, statDisc: 4, statCrea: 0);
        $user = new User();
        $monster = $this->monster(AffinityStat::NEUTRAL, bossLevel: 1);

        $preview = $service->preview($user, $monster, 20);

        self::assertSame(6, $preview['breakdown']['statPower']);
        self::assertSame(26, $preview['estimatedDamage']);
    }

    public function testLevelModifierThreeLevelsBelowBoss(): void
    {
        $service = $this->createService(playerXp: 0);
        $user = new User();
        $monster = $this->monster(AffinityStat::NEUTRAL, bossLevel: 4);

        $preview = $service->preview($user, $monster, 20);

        self::assertSame(0.7, $preview['breakdown']['levelMult']);
        self::assertSame(14, $preview['estimatedDamage']);
    }

    public function testDocStyleBreakdownWithItemsAndLevelGap(): void
    {
        $inventory = $this->createMock(InventoryRepository::class);
        $inventory->method('findEquippedForUser')->willReturn([]);

        $statRepository = $this->createMock(StatRepository::class);
        $user = new User();
        $stat = (new Stat())->setUser($user);
        $stat->addPoints(StatType::FORCE, 20);
        $stat->addPoints(StatType::INTELLIGENCE, 4);
        $statRepository->method('findOneByUser')->willReturn($stat);

        $eventRepository = $this->createMock(EventRepository::class);
        $eventRepository->method('findActiveAt')->willReturn([]);
        $eventMultiplier = new EventMultiplierService($eventRepository);

        $service = new DamagePreviewService(
            new StatService($this->createMock(EntityManagerInterface::class), $statRepository, new NullLogger()),
            $inventory,
            $eventMultiplier,
            new LevelService(),
        );

        $monster = $this->monster(AffinityStat::FORCE, bossLevel: 6);
        $user->addXp(400);

        $preview = $service->preview($user, $monster, 20);

        self::assertSame(20, $preview['breakdown']['questBase']);
        self::assertGreaterThan(0, $preview['breakdown']['statPower']);
        self::assertLessThan(1.0, $preview['breakdown']['levelMult']);
        self::assertGreaterThanOrEqual(0.45, $preview['breakdown']['levelMult']);
        self::assertGreaterThanOrEqual(1, $preview['estimatedDamage']);
    }

    public function testRawZeroYieldsZeroDamage(): void
    {
        $service = $this->createService();
        $user = new User();
        $monster = $this->monster(AffinityStat::NEUTRAL, bossLevel: 1);

        self::assertSame(0, $service->calculateFinalDamage($user, $monster, 0));
    }

    private function createService(
        int $statForce = 0,
        int $statInt = 0,
        int $statDisc = 0,
        int $statCrea = 0,
        int $playerXp = 0,
    ): DamagePreviewService {
        $statRepository = $this->createMock(StatRepository::class);
        $user = new User();
        $user->addXp($playerXp);
        $stat = (new Stat())->setUser($user);
        if ($statForce > 0) {
            $stat->addPoints(StatType::FORCE, $statForce);
        }
        if ($statInt > 0) {
            $stat->addPoints(StatType::INTELLIGENCE, $statInt);
        }
        if ($statDisc > 0) {
            $stat->addPoints(StatType::DISCIPLINE, $statDisc);
        }
        if ($statCrea > 0) {
            $stat->addPoints(StatType::CREATIVITY, $statCrea);
        }
        $statRepository->method('findOneByUser')->willReturn($stat);

        $inventory = $this->createMock(InventoryRepository::class);
        $inventory->method('findEquippedForUser')->willReturn([]);

        $eventRepository = $this->createMock(EventRepository::class);
        $eventRepository->method('findActiveAt')->willReturn([]);

        return new DamagePreviewService(
            new StatService($this->createMock(EntityManagerInterface::class), $statRepository, new NullLogger()),
            $inventory,
            new EventMultiplierService($eventRepository),
            new LevelService(),
        );
    }

    private function monster(AffinityStat $affinity, int $bossLevel): UserMonster
    {
        $user = new User();
        $template = (new MonsterTemplate())
            ->setName('Boss')
            ->setBaseHp(100)
            ->setRarity(Rarity::COMMON)
            ->setAffinityStat($affinity)
            ->setBossLevel($bossLevel);

        return (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setMaxHp(100)
            ->setCurrentHp(100);
    }
}

