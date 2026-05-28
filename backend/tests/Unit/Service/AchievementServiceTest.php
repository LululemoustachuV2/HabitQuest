<?php

namespace App\Tests\Unit\Service;

use App\Entity\Achievement;
use App\Entity\User;
use App\Enum\AchievementCode;
use App\Repository\AchievementRepository;
use App\Repository\UserAchievementRepository;
use App\Repository\UserMonsterRepository;
use App\Repository\UserQuestRepository;
use App\Service\AchievementService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AchievementServiceTest extends TestCase
{
    public function testCheckAfterQuestValidatedUnlocksFirstQuestOnce(): void
    {
        $user = new User();

        $achievement = (new Achievement())
            ->setCode(AchievementCode::FIRST_QUEST_VALIDATED)
            ->setName('Première quête')
            ->setDescription('Valider une quête.');

        $userQuestRepo = $this->createMock(UserQuestRepository::class);
        $userQuestRepo->method('countValidatedForUser')->willReturn(1);

        $userAchievementRepo = $this->createMock(UserAchievementRepository::class);
        $userAchievementRepo
            ->method('hasUnlockedCode')
            ->willReturnOnConsecutiveCalls(false, true);

        $achievementRepo = $this->createMock(AchievementRepository::class);
        $achievementRepo
            ->method('findOneByCode')
            ->with(AchievementCode::FIRST_QUEST_VALIDATED)
            ->willReturn($achievement);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');

        $service = new AchievementService(
            $em,
            $achievementRepo,
            $userAchievementRepo,
            $userQuestRepo,
            $this->createMock(UserMonsterRepository::class),
        );

        $first = $service->checkAfterQuestValidated($user);
        $second = $service->checkAfterQuestValidated($user);

        self::assertSame(['first_quest_validated'], $first);
        self::assertSame([], $second);
    }

    public function testCheckAfterMonsterDeathUnlocksOnce(): void
    {
        $user = new User();
        $achievement = (new Achievement())
            ->setCode(AchievementCode::FIRST_MONSTER_KILL)
            ->setName('Tueur de Monstre')
            ->setDescription('Première mort.');

        $monsterRepo = $this->createMock(UserMonsterRepository::class);
        $monsterRepo->method('countDefeatedForUser')->willReturn(1);

        $userAchievementRepo = $this->createMock(UserAchievementRepository::class);
        $userAchievementRepo
            ->method('hasUnlockedCode')
            ->willReturnOnConsecutiveCalls(false, true);

        $achievementRepo = $this->createMock(AchievementRepository::class);
        $achievementRepo
            ->method('findOneByCode')
            ->with(AchievementCode::FIRST_MONSTER_KILL)
            ->willReturn($achievement);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');

        $service = new AchievementService(
            $em,
            $achievementRepo,
            $userAchievementRepo,
            $this->createMock(UserQuestRepository::class),
            $monsterRepo,
        );

        self::assertSame(['first_monster_kill'], $service->checkAfterMonsterDeath($user));
        self::assertSame([], $service->checkAfterMonsterDeath($user));
    }
}

