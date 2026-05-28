<?php

namespace App\Tests\Unit\Service;

use App\Entity\Stat;
use App\Entity\User;
use App\Enum\StatType;
use App\Repository\StatRepository;
use App\Service\StatService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StatServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private StatRepository $statRepository;

    private StatService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->statRepository = $this->createMock(StatRepository::class);

        $this->service = new StatService($this->entityManager, $this->statRepository);
    }

    public function testInitializeForUserPersistsNewStatWhenAbsent(): void
    {
        $user = new User();
        $this->statRepository
            ->expects(self::once())
            ->method('findOneByUser')
            ->with($user)
            ->willReturn(null);

        $persisted = null;
        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (object $arg) use (&$persisted): bool {
                $persisted = $arg;

                return $arg instanceof Stat;
            }));
        $this->entityManager->expects(self::never())->method('flush');

        $stat = $this->service->initializeForUser($user);

        self::assertSame($persisted, $stat);
        self::assertSame($user, $stat->getUser());
        self::assertSame(0, $stat->getForce());
        self::assertSame(0, $stat->getIntelligence());
        self::assertSame(0, $stat->getDiscipline());
        self::assertSame(0, $stat->getCreativity());
    }

    public function testInitializeForUserIsIdempotent(): void
    {
        $user = new User();
        $existing = (new Stat())->setUser($user);
        $this->statRepository
            ->expects(self::once())
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existing);

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');

        self::assertSame($existing, $this->service->initializeForUser($user));
    }

    public function testAddStatPointsRejectsNegativePoints(): void
    {
        $this->statRepository->expects(self::never())->method('findOneByUser');
        $this->entityManager->expects(self::never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);

        $this->service->addStatPoints(new User(), StatType::FORCE, -5, StatService::SOURCE_HABIT_LOG);
    }

    public function testAddStatPointsRejectsEmptySource(): void
    {
        $this->statRepository->expects(self::never())->method('findOneByUser');
        $this->entityManager->expects(self::never())->method('flush');

        $this->expectException(\InvalidArgumentException::class);

        $this->service->addStatPoints(new User(), StatType::FORCE, 1, '');
    }

    public function testAddStatPointsWithZeroIsNoopButStillReturnsStat(): void
    {
        $user = new User();
        $existing = (new Stat())->setUser($user);

        $this->statRepository
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existing);
        $this->entityManager->expects(self::never())->method('flush');

        $result = $this->service->addStatPoints(
            $user,
            StatType::INTELLIGENCE,
            0,
            StatService::SOURCE_QUEST_REWARD
        );

        self::assertSame($existing, $result);
        self::assertSame(0, $result->getIntelligence());
    }

    public function testAddStatPointsIncrementsExistingStatAndFlushes(): void
    {
        $user = new User();
        $existing = (new Stat())->setUser($user);

        $this->statRepository
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existing);
        $this->entityManager->expects(self::once())->method('flush');

        $result = $this->service->addStatPoints(
            $user,
            StatType::DISCIPLINE,
            7,
            StatService::SOURCE_ITEM_EQUIPPED
        );

        self::assertSame($existing, $result);
        self::assertSame(7, $result->getDiscipline());
    }

    public function testGrantLevelUpBonusesAddsOnePointPerStatPerLevelGained(): void
    {
        $user = new User();
        $existing = (new Stat())->setUser($user);

        $this->statRepository
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existing);
        $this->entityManager->expects(self::once())->method('flush');

        $result = $this->service->grantLevelUpBonuses($user, oldLevel: 1, newLevel: 3);

        self::assertSame(2, $result->getForce());
        self::assertSame(2, $result->getIntelligence());
        self::assertSame(2, $result->getDiscipline());
        self::assertSame(2, $result->getCreativity());
    }

    public function testGrantLevelUpBonusesIsNoopWhenLevelUnchanged(): void
    {
        $user = new User();
        $existing = (new Stat())->setUser($user);

        $this->statRepository
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($existing);
        $this->entityManager->expects(self::never())->method('flush');

        $result = $this->service->grantLevelUpBonuses($user, oldLevel: 2, newLevel: 2);

        self::assertSame(0, $result->getForce());
    }

    public function testAddStatPointsCreatesStatOnTheFlyForLegacyUsers(): void
    {
        $user = new User();
        $this->statRepository
            ->method('findOneByUser')
            ->with($user)
            ->willReturn(null);
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Stat::class));
        $this->entityManager->expects(self::exactly(2))->method('flush');

        $result = $this->service->addStatPoints(
            $user,
            StatType::CREATIVITY,
            3,
            StatService::SOURCE_EVENT
        );

        self::assertSame(3, $result->getCreativity());
        self::assertSame($user, $result->getUser());
    }
}

