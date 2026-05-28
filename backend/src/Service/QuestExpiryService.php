<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

final class QuestExpiryService
{
    private const PARIS = 'Europe/Paris';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
    ) {
    }

    public function expireInProgressQuestsForUser(User $user): void
    {
        $nowParis = new \DateTimeImmutable('now', new \DateTimeZone(self::PARIS));
        $quests = $this->userQuestRepository->findAllForUser($user);
        $changed = false;

        foreach ($quests as $quest) {
            if ($quest->getStatus() !== UserQuestStatus::IN_PROGRESS) {
                continue;
            }
            if (!$this->isQuestWindowElapsed($quest, $nowParis)) {
                continue;
            }
            $quest->markExpired();
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    public function isQuestWindowElapsed(UserQuest $quest, ?\DateTimeImmutable $nowParis = null): bool
    {
        if ($quest->getStatus() !== UserQuestStatus::IN_PROGRESS) {
            return $quest->getStatus() === UserQuestStatus::EXPIRED;
        }

        $nowParis ??= new \DateTimeImmutable('now', new \DateTimeZone(self::PARIS));
        $event = $quest->getEvent();

        if ($event !== null) {
            return $event->getEndsAt() <= $nowParis;
        }

        $kind = $quest->getQuestTemplate()->getKind();
        $startedParis = $quest->getStartedAt()->setTimezone(new \DateTimeZone(self::PARIS));

        if ($kind === QuestKind::DAILY) {
            return $startedParis->format('Y-m-d') < $nowParis->format('Y-m-d');
        }

        if ($kind === QuestKind::WEEKLY) {
            $weekStart = $startedParis->modify('monday this week')->setTime(0, 0);
            $expiresAt = $weekStart->modify('+7 days');

            return $nowParis >= $expiresAt;
        }

        return false;
    }
}

