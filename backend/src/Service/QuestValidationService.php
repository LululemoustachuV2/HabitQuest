<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Notification;
use App\Entity\QuestReward;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Entity\UserQuest;
use App\Entity\UserQuestActionLog;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\EventRepository;
use App\Repository\NotificationRepository;
use App\Repository\QuestConditionRepository;
use App\Repository\QuestRewardRepository;
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
        private readonly QuestRewardRepository $questRewardRepository,
        private readonly QuestConditionRepository $questConditionRepository,
        private readonly InventoryService $inventoryService,
        private readonly EventMultiplierService $eventMultiplierService,
        private readonly LevelService $levelService,
        private readonly StatService $statService,
        private readonly UserMonsterService $userMonsterService,
        private readonly CombatService $combatService,
        private readonly MonsterService $monsterService,
        private readonly AchievementService $achievementService,
    ) {
    }

    public function completeQuestAfterConditions(User $user, UserQuest $userQuest, string $comment): void
    {
        if ($userQuest->getStatus() === UserQuestStatus::COMPLETED) {
            return;
        }

        $this->applyQuestCompletion($user, $userQuest, $comment);
        $this->entityManager->flush();
    }

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

        $template = $userQuest->getQuestTemplate();
        if ($this->questConditionRepository->count(['questTemplate' => $template]) > 0) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Cette quête se complète automatiquement lorsque ses conditions sont remplies.',
            ];
        }

        if ($template->getKind() === QuestKind::PROGRESSION) {
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

        $completion = $this->applyQuestCompletion($currentUser, $userQuest, $comment);
        $this->entityManager->flush();

        $response = [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Quête validée.',
            'userQuestId' => $userQuestId,
            'comment' => $comment,
            'xpAwarded' => $completion['xpReward'],
            'goldAwarded' => $completion['goldReward'],
        ];

        if ($completion['itemGranted'] !== null) {
            $response['itemGranted'] = $completion['itemGranted'];
        }
        if ($completion['leveledUp']) {
            $response['leveledUp'] = true;
            $response['newLevel'] = $completion['newLevel'];
        }
        if ($completion['statRewardsGranted'] !== []) {
            $response['statRewardsGranted'] = $completion['statRewardsGranted'];
        }

        $response['damageDealt'] = $completion['damageDealt'];
        $response['monsterDied'] = $completion['monsterDied'];
        if ($completion['loot'] !== null) {
            $response['loot'] = $completion['loot'];
        }

        return $response;
    }

    private function applyQuestCompletion(User $user, UserQuest $userQuest, string $comment): array
    {
        $isEventQuest = $userQuest->getEvent() instanceof Event;
        $template = $userQuest->getQuestTemplate();
        $composedReward = $this->questRewardRepository->findOneByQuestTemplate($template);
        $previousXp = $user->getXp();

        $xpReward = 0;
        $goldReward = 0;
        $itemGranted = null;
        $statRewardsGranted = [];

        if ($isEventQuest) {
        } elseif ($composedReward instanceof QuestReward) {
            $xpReward = $composedReward->getXp();
            $goldReward = $composedReward->getGold();
        } else {
            $xpReward = $template->getXpReward();
        }

        $userQuest->markCompleted();

        if (!$isEventQuest) {
            $xpReward = $this->eventMultiplierService->applyXp($xpReward);
            $goldReward = $this->eventMultiplierService->applyGold($goldReward);
        }

        $combat = $this->applyQuestCombat($user, $template);

        if ($xpReward > 0) {
            $user->addXp($xpReward);
        }
        if ($goldReward > 0) {
            $user->addGold($goldReward);
        }
        if ($composedReward instanceof QuestReward && $composedReward->getItem() !== null) {
            $inventoryEntry = $this->inventoryService->grantItem(
                $user,
                $composedReward->getItem(),
                flush: false
            );
            $itemGranted = [
                'inventoryId' => $inventoryEntry->getId(),
                'itemId' => $composedReward->getItem()->getId(),
                'itemName' => $composedReward->getItem()->getName(),
            ];
        }

        if ($composedReward instanceof QuestReward && !$isEventQuest) {
            $statRewardsGranted = QuestRewardStatHelper::formatForApi($composedReward->getParams());
            QuestRewardStatHelper::applyStatBonuses($user, $composedReward->getParams(), $this->statService);
        }

        $levelInfo = $this->levelService->checkLevelUp($user, $previousXp);
        if ($levelInfo['leveledUp']) {
            $this->statService->grantLevelUpBonuses(
                $user,
                $levelInfo['oldLevel'],
                $levelInfo['newLevel'],
                flush: false
            );
        }

        $log = (new UserQuestActionLog())
            ->setUser($user)
            ->setUserQuest($userQuest)
            ->setComment($comment);

        $notification = (new Notification())
            ->setUser($user)
            ->setTitle('Quête complétée')
            ->setBody(
                $isEventQuest
                    ? sprintf(
                        'Tu as validé « %s ». Les XP de cet événement te seront attribués à sa clôture.',
                        $template->getTitle()
                    )
                    : $this->buildCompletionNotificationBody($template->getTitle(), $xpReward, $goldReward, $itemGranted)
            );

        $this->entityManager->persist($log);
        $this->entityManager->persist($notification);
        $this->settleEndedEventRewards($user);
        $this->achievementService->checkAfterQuestValidated($user);

        return [
            'xpReward' => $xpReward,
            'goldReward' => $goldReward,
            'itemGranted' => $itemGranted,
            'leveledUp' => $levelInfo['leveledUp'],
            'newLevel' => $levelInfo['newLevel'],
            'statRewardsGranted' => $statRewardsGranted,
            'damageDealt' => $combat['damageDealt'],
            'monsterDied' => $combat['monsterDied'],
            'loot' => $combat['loot'],
        ];
    }

    private function applyQuestCombat(User $user, QuestTemplate $template): array
    {
        try {
            $monster = $this->userMonsterService->getOrSpawnActiveMonster($user);
        } catch (\RuntimeException) {
            return [
                'damageDealt' => 0,
                'monsterDied' => false,
                'loot' => null,
            ];
        }

        $combatResult = $this->combatService->applyDamageFromQuestBase(
            $user,
            $monster,
            $template->getBaseDamage()
        );

        $loot = null;
        if ($combatResult['monsterDied']) {
            $deathResult = $this->monsterService->onMonsterDeath($user, $monster);
            $loot = $deathResult['loot'];
        }

        return [
            'damageDealt' => $combatResult['damage'],
            'monsterDied' => $combatResult['monsterDied'],
            'loot' => $loot,
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

    private function buildCompletionNotificationBody(
        string $title,
        int $xpReward,
        int $goldReward,
        ?array $itemGranted,
    ): string {
        $parts = [sprintf('Tu as validé « %s »', $title)];

        if ($xpReward > 0) {
            $parts[] = sprintf('%d XP', $xpReward);
        }
        if ($goldReward > 0) {
            $parts[] = sprintf('%d gold', $goldReward);
        }
        if ($itemGranted !== null) {
            $parts[] = sprintf('l\'item « %s »', $itemGranted['itemName']);
        }

        if (count($parts) === 1) {
            return $parts[0].'.';
        }

        $rewards = array_slice($parts, 1);
        $last = array_pop($rewards);
        $rewardText = $rewards === [] ? $last : implode(', ', $rewards).' et '.$last;

        return $parts[0].' et gagné '.$rewardText.'.';
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

