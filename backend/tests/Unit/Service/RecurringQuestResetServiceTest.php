<?php

namespace App\Tests\Unit\Service;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\UserQuestRepository;
use App\Service\QuestExpiryService;
use App\Service\RecurringQuestResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RecurringQuestResetServiceTest extends TestCase
{
    public function testRemovesExpiredDailyQuestSoNewOneCanBeSynced(): void
    {
        $user = new User();
        $template = (new QuestTemplate())
            ->setKind(QuestKind::DAILY)
            ->setTitle('Daily test')
            ->setXpReward(10)
            ->setIsActive(true);

        $expired = (new UserQuest())
            ->setUser($user)
            ->setQuestTemplate($template)
            ->setStartedAt(new \DateTimeImmutable('yesterday'));
        $expired->markExpired();

        $repo = $this->createMock(UserQuestRepository::class);
        $repo->method('findAllForUser')->willReturn([$expired]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($expired);
        $em->expects(self::once())->method('flush');

        $expiry = new QuestExpiryService($em, $repo);

        $service = new RecurringQuestResetService($em, $repo, $expiry);
        $service->resetForUser($user);
    }
}

