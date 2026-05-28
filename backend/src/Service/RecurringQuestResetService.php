<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RecurringQuestResetService
{
    private const PARIS = 'Europe/Paris';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestExpiryService $questExpiryService,
    ) {
    }

    public function resetForUser(User $user): void
    {
        $nowParis = new \DateTimeImmutable('now', new \DateTimeZone(self::PARIS));
        $removed = false;

        foreach ($this->userQuestRepository->findAllForUser($user) as $quest) {
            if ($quest->getEvent() !== null) {
                continue;
            }

            $kind = $quest->getQuestTemplate()->getKind();
            if ($kind !== QuestKind::DAILY && $kind !== QuestKind::WEEKLY) {
                continue;
            }

            if ($this->shouldRemoveRecurringQuest($quest, $nowParis)) {
                $this->entityManager->remove($quest);
                $removed = true;
            }
        }

        if ($removed) {
            $this->entityManager->flush();
        }
    }

    private function shouldRemoveRecurringQuest(UserQuest $quest, \DateTimeImmutable $nowParis): bool
    {
        $status = $quest->getStatus();
        $kind = $quest->getQuestTemplate()->getKind();

        if ($status === UserQuestStatus::EXPIRED) {
            return true;
        }

        if ($status === UserQuestStatus::IN_PROGRESS) {
            return $this->questExpiryService->isQuestWindowElapsed($quest, $nowParis);
        }

        if ($status !== UserQuestStatus::COMPLETED) {
            return false;
        }

        $completedAt = $quest->getCompletedAt();
        if (!$completedAt instanceof \DateTimeImmutable) {
            return true;
        }

        $completedParis = $completedAt->setTimezone(new \DateTimeZone(self::PARIS));

        if ($kind === QuestKind::DAILY) {
            $nextReset = $completedParis->setTime(0, 0)->modify('+1 day');

            return $nowParis >= $nextReset;
        }

        if ($kind === QuestKind::WEEKLY) {
            $weekStart = $completedParis->modify('monday this week')->setTime(0, 0);
            $nextReset = $weekStart->modify('+7 days');

            return $nowParis >= $nextReset;
        }

        return false;
    }
}

