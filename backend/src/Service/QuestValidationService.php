<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\UserQuestActionLog;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\EventRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

final class QuestValidationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly EventRepository $eventRepository,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * Règle MVP : validation simple, une seule attribution de récompense par quête.
     */
    public function validateQuestForCurrentUser(int $userQuestId, string $comment): array
    {
        if ($userQuestId <= 0) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Identifiant de quête invalide.',
            ];
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return [
                'statusCode' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Utilisateur non authentifié.',
            ];
        }

        $userQuest = $this->userQuestRepository->find($userQuestId);
        if (!$userQuest instanceof UserQuest) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Quête utilisateur introuvable.',
            ];
        }

        if ($userQuest->getUser()->getId() !== $currentUser->getId()) {
            return [
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => 'Accès interdit à cette quête.',
            ];
        }

        if ($userQuest->getStatus() === UserQuestStatus::COMPLETED) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Quête déjà complétée.',
            ];
        }

        if ($userQuest->getStatus() === UserQuestStatus::EXPIRED) {
            return [
                'statusCode' => Response::HTTP_CONFLICT,
                'message' => 'Quête expirée : elle ne peut plus être validée.',
            ];
        }

        if ($userQuest->getQuestTemplate()->getKind() === QuestKind::PROGRESSION) {
            $levelData = $this->computeLevelData($currentUser->getXp());
            if ($levelData['level'] < $userQuest->getQuestTemplate()->getRequiredLevel()) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => sprintf(
                        'Niveau insuffisant : niveau requis %d, niveau actuel %d.',
                        $userQuest->getQuestTemplate()->getRequiredLevel(),
                        $levelData['level']
                    ),
                ];
            }
        }

        $isEventQuest = $userQuest->getEvent() instanceof Event;
        $xpReward = $isEventQuest ? 0 : $userQuest->getQuestTemplate()->getXpReward();
        $userQuest->markCompleted();
        $currentUser->addXp($xpReward);

        $log = (new UserQuestActionLog())
            ->setUser($currentUser)
            ->setUserQuest($userQuest)
            ->setComment($comment);

        $notification = (new Notification())
            ->setUser($currentUser)
            ->setTitle('Quête complétée')
            ->setBody(
                $isEventQuest
                    ? sprintf(
                        'Tu as validé « %s ». Les XP de cet événement te seront attribués à sa clôture.',
                        $userQuest->getQuestTemplate()->getTitle()
                    )
                    : sprintf('Tu as validé « %s » et gagné %d XP.', $userQuest->getQuestTemplate()->getTitle(), $xpReward)
            );

        $this->entityManager->persist($log);
        $this->entityManager->persist($notification);
        $this->settleEndedEventRewards($currentUser);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Quête validée.',
            'userQuestId' => $userQuestId,
            'comment' => $comment,
            'xpAwarded' => $xpReward,
        ];
    }

    public function settleEndedEventRewards(User $user): void
    {
        $endedEventsRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(uq.event) AS eventId')
            ->from(UserQuest::class, 'uq')
            ->where('uq.user = :user')
            ->andWhere('uq.event IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        $eventIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['eventId'], $endedEventsRows)));
        if ($eventIds === []) {
            return;
        }

        $events = $this->eventRepository->findBy(['id' => $eventIds]);
        $now = new \DateTimeImmutable();

        foreach ($events as $event) {
            if ($event->getEndsAt() > $now) {
                continue;
            }

            if ($this->hasEventRewardAlreadyGranted($user, $event)) {
                continue;
            }

            $eventUserQuests = $this->userQuestRepository->findBy([
                'user' => $user,
                'event' => $event,
            ]);

            if ($eventUserQuests === []) {
                continue;
            }

            $allCompleted = true;
            foreach ($eventUserQuests as $eventUserQuest) {
                if ($eventUserQuest->getStatus() !== UserQuestStatus::COMPLETED) {
                    $allCompleted = false;
                    break;
                }
            }

            if (!$allCompleted) {
                continue;
            }

            $rewardXp = $event->getXpReward();
            if ($rewardXp > 0) {
                $user->addXp($rewardXp);
            }

            $this->entityManager->persist(
                (new Notification())
                    ->setUser($user)
                    ->setTitle(sprintf('Récompense événement #%d', $event->getId()))
                    ->setBody(sprintf(
                        'Événement terminé : tu as reçu %d XP pour l\'événement #%d.',
                        $rewardXp,
                        $event->getId()
                    ))
            );
        }
    }

    private function hasEventRewardAlreadyGranted(User $user, Event $event): bool
    {
        $existing = $this->notificationRepository->findOneBy([
            'user' => $user,
            'title' => sprintf('Récompense événement #%d', $event->getId()),
        ]);

        return $existing instanceof Notification;
    }

    private function computeLevelData(int $xp): array
    {
        $remainingXp = max(0, $xp);
        $level = 1;
        $xpRequiredForLevel = 100;

        while ($remainingXp >= $xpRequiredForLevel) {
            $remainingXp -= $xpRequiredForLevel;
            ++$level;
            $xpRequiredForLevel = (int) ceil($xpRequiredForLevel * 1.05);
        }

        return [
            'level' => $level,
            'xpIntoLevel' => $remainingXp,
            'xpRequiredForNextLevel' => $xpRequiredForLevel,
        ];
    }
}
