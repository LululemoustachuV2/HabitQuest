<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\StatRepository;
use App\Service\QuestRewardStatHelper;
use App\Service\StatService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class QuestRewardStatHelperTest extends TestCase
{
    public function testParseStatBonusesFromParams(): void
    {
        $bonuses = QuestRewardStatHelper::parseStatBonuses([
            'stats' => [
                'force' => 3,
                'creativity' => '2',
                'invalid' => 5,
                'discipline' => 0,
            ],
        ]);

        self::assertSame(['force' => 3, 'creativity' => 2], $bonuses);
    }

    public function testFormatForApiReturnsOrderedEntries(): void
    {
        $formatted = QuestRewardStatHelper::formatForApi([
            'stats' => ['force' => 3],
        ]);

        self::assertSame([['stat' => 'force', 'points' => 3]], $formatted);
    }

    public function testApplyStatBonusesDelegatesToStatService(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $statRepository = $this->createMock(StatRepository::class);
        $statService = new StatService($entityManager, $statRepository);

        $user = new User();
        $statRepository->method('findOneByUser')->willReturn((new \App\Entity\Stat())->setUser($user));
        $entityManager->expects(self::once())->method('flush');

        QuestRewardStatHelper::applyStatBonuses($user, ['stats' => ['force' => 2]], $statService);
    }
}

