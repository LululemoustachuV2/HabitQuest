<?php

namespace App\Tests\Unit\Service;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\UserQuestRepository;
use App\Service\QuestExpiryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class QuestExpiryServiceTest extends TestCase
{
    public function testDailyQuestStartedYesterdayIsElapsed(): void
    {
        $service = new QuestExpiryService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserQuestRepository::class)
        );

        $template = (new QuestTemplate())
            ->setTitle('Daily')
            ->setKind(QuestKind::DAILY)
            ->setXpReward(10)
            ->setRequiredLevel(1)
            ->setIsActive(true);

        $quest = (new UserQuest())
            ->setUser(new User())
            ->setQuestTemplate($template)
            ->setStartedAt(new \DateTimeImmutable('yesterday', new \DateTimeZone('Europe/Paris')));

        self::assertTrue($service->isQuestWindowElapsed($quest));
    }

    public function testProgressionQuestNeverExpiresByTime(): void
    {
        $service = new QuestExpiryService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserQuestRepository::class)
        );

        $template = (new QuestTemplate())
            ->setTitle('Story')
            ->setKind(QuestKind::PROGRESSION)
            ->setXpReward(10)
            ->setRequiredLevel(1)
            ->setIsActive(true);

        $quest = (new UserQuest())
            ->setUser(new User())
            ->setQuestTemplate($template)
            ->setStartedAt(new \DateTimeImmutable('-30 days'));

        self::assertFalse($service->isQuestWindowElapsed($quest));
    }
}

