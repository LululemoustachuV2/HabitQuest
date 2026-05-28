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
use App\Service\CombatService;
use App\Service\DamagePreviewService;
use App\Service\EventMultiplierService;
use App\Service\LevelService;
use App\Service\StatService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CombatServiceTest extends TestCase
{
    public function testUsesQuestBaseDamageNotXp(): void
    {
        $combatService = $this->createCombatService(statForce: 0);

        $user = new User();
        $template = (new MonsterTemplate())
            ->setName('Neutre')
            ->setBaseHp(100)
            ->setAffinityStat(AffinityStat::NEUTRAL)
            ->setBossLevel(1);
        $monster = (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setMaxHp(100)
            ->setCurrentHp(100);

        self::assertSame(15, $combatService->calculateDamage($user, $monster, 15));
        self::assertSame(15, $combatService->calculateDamage($user, $monster, 15));
    }

    public function testAffinityIncreasesDamageViaStatPower(): void
    {
        $combatService = $this->createCombatService(statForce: 25);

        $user = new User();
        $template = (new MonsterTemplate())
            ->setName('Forceux')
            ->setBaseHp(100)
            ->setAffinityStat(AffinityStat::FORCE)
            ->setBossLevel(1);
        $monster = (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setMaxHp(100)
            ->setCurrentHp(100);

        self::assertSame(41, $combatService->calculateDamage($user, $monster, 10));
    }

    public function testDealDamageNeverGoesBelowZeroHp(): void
    {
        $combatService = $this->createCombatService(statForce: 0);

        $user = new User();
        $template = (new MonsterTemplate())
            ->setName('Fragile')
            ->setBaseHp(10)
            ->setRarity(Rarity::COMMON)
            ->setAffinityStat(AffinityStat::NEUTRAL)
            ->setBossLevel(1);
        $monster = (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setMaxHp(10)
            ->setCurrentHp(5);

        $result = $combatService->dealDamage($monster, 999);

        self::assertSame(999, $result['damage']);
        self::assertTrue($result['monsterDied']);
        self::assertSame(0, $result['currentHp']);
    }

    private function createCombatService(int $statForce): CombatService
    {
        $statRepository = $this->createMock(StatRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $user = new User();
        $stat = (new Stat())->setUser($user);
        if ($statForce > 0) {
            $stat->addPoints(StatType::FORCE, $statForce);
        }

        $statRepository->method('findOneByUser')->willReturn($stat);

        $statService = new StatService($entityManager, $statRepository, new NullLogger());
        $inventory = $this->createMock(InventoryRepository::class);
        $inventory->method('findEquippedForUser')->willReturn([]);
        $eventRepository = $this->createMock(EventRepository::class);
        $eventRepository->method('findActiveAt')->willReturn([]);

        $damagePreview = new DamagePreviewService(
            $statService,
            $inventory,
            new EventMultiplierService($eventRepository),
            new LevelService(),
        );

        return new CombatService($damagePreview);
    }
}

