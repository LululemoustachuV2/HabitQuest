<?php

namespace App\Controller\Api;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserQuestRepository;
use App\Service\QuestValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserQuestController extends AbstractController
{
    public function __construct(
        private readonly QuestValidationService $questValidationService,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/quests', name: 'api_user_quests_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->questValidationService->settleEndedEventRewards($user);
        $this->resetRecurringQuests($user);
        $this->syncMissingStandardQuestsForUser($user);
        $this->entityManager->flush();

        $currentLevel = $this->computeLevelData($user->getXp())['level'];

        $quests = $this->userQuestRepository->findAllForUser($user);
        $response = [
            'active' => [],
            'completed' => [],
            'expired' => [],
            'activeByKind' => [
                'daily' => [],
                'weekly' => [],
                'progression' => [],
                'event' => [],
            ],
            'eventQuests' => [],
        ];

        foreach ($quests as $quest) {
            $eventEndsAt = $quest->getEvent()?->getEndsAt();
            $timing = $this->buildQuestTiming($quest, $eventEndsAt);
            $item = [
                'id' => $quest->getId(),
                'title' => $quest->getQuestTemplate()->getTitle(),
                'description' => $quest->getQuestTemplate()->getDescription(),
                'kind' => $quest->getQuestTemplate()->getKind()->value,
                'xpReward' => $quest->getQuestTemplate()->getXpReward(),
                'requiredLevel' => $quest->getQuestTemplate()->getRequiredLevel(),
                'isUnlocked' => $currentLevel >= $quest->getQuestTemplate()->getRequiredLevel(),
                'isEvent' => $quest->getEvent() !== null,
                'eventId' => $quest->getEvent()?->getId(),
                'eventEndsAt' => $eventEndsAt?->format(\DateTimeInterface::ATOM),
                'eventXpReward' => $quest->getEvent()?->getXpReward(),
                'status' => $quest->getStatus()->value,
                'isValidated' => $quest->isValidated(),
                'timing' => $quest->getStatus() === UserQuestStatus::IN_PROGRESS ? $timing : [
                    'resetType' => 'none',
                    'endsAt' => null,
                    'remainingSeconds' => null,
                ],
            ];

            if ($quest->getStatus() === UserQuestStatus::COMPLETED) {
                $response['completed'][] = $item;
            } elseif ($quest->getStatus() === UserQuestStatus::EXPIRED) {
                $response['expired'][] = $item;
            } else {
                $response['active'][] = $item;
                if (!($item['kind'] === 'event' && $item['isEvent'] === true)) {
                    $response['activeByKind'][$item['kind']][] = $item;
                }
            }

            if ($item['isEvent']) {
                $response['eventQuests'][] = $item;
            }
        }

        return $this->json([
            'active' => $response['active'],
            'completed' => $response['completed'],
            'expired' => $response['expired'],
            'activeByKind' => $response['activeByKind'],
            'eventQuests' => $response['eventQuests'],
        ]);
    }

    #[Route('/api/quests/{id}/validate', name: 'api_user_quests_validate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validateQuest(int $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $comment = is_array($payload) && isset($payload['comment'])
            ? (string) $payload['comment']
            : 'Validation simple MVP';

        $result = $this->questValidationService->validateQuestForCurrentUser($id, $comment);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/me/progression', name: 'api_user_progression', methods: ['GET'])]
    public function progression(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->questValidationService->settleEndedEventRewards($user);
        $this->entityManager->flush();

        $xp = $user->getXp();
        $levelData = $this->computeLevelData($xp);

        return $this->json([
            'xp' => $xp,
            'level' => $levelData['level'],
            'xpIntoLevel' => $levelData['xpIntoLevel'],
            'xpRequiredForNextLevel' => $levelData['xpRequiredForNextLevel'],
            'xpToNextLevel' => $levelData['xpToNextLevel'],
            'progressPercent' => $levelData['progressPercent'],
        ]);
    }

    /**
     * Réinitialise les quêtes quotidiennes/hebdomadaires terminées si la période
     * de reset (minuit Europe/Paris / lundi minuit) est passée depuis la complétion.
     */
    private function resetRecurringQuests(User $user): void
    {
        $nowParis = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        $quests = $this->userQuestRepository->findAllForUser($user);
        foreach ($quests as $quest) {
            if ($quest->getStatus() !== UserQuestStatus::COMPLETED) {
                continue;
            }
            if ($quest->getEvent() !== null) {
                continue;
            }

            $kind = $quest->getQuestTemplate()->getKind();
            $completedAt = $quest->getCompletedAt();
            if (!$completedAt instanceof \DateTimeImmutable) {
                continue;
            }
            $completedAtParis = $completedAt->setTimezone(new \DateTimeZone('Europe/Paris'));

            if ($kind === QuestKind::DAILY) {
                $nextReset = $completedAtParis->setTime(0, 0)->modify('+1 day');
                if ($nowParis >= $nextReset) {
                    $this->entityManager->remove($quest);
                }
            } elseif ($kind === QuestKind::WEEKLY) {
                $dayOfWeek = (int) $completedAtParis->format('N');
                $daysUntilMonday = 8 - $dayOfWeek;
                if ($daysUntilMonday === 7) {
                    $daysUntilMonday = 7;
                }
                $nextReset = $completedAtParis->setTime(0, 0)->modify("+{$daysUntilMonday} days");
                if ($nowParis >= $nextReset) {
                    $this->entityManager->remove($quest);
                }
            }
        }
    }

    private function buildQuestTiming(UserQuest $quest, ?\DateTimeImmutable $eventEndsAt): array
    {
        $nowParis = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $kind = $quest->getQuestTemplate()->getKind()->value;

        if ($eventEndsAt instanceof \DateTimeImmutable) {
            $remaining = max(0, $eventEndsAt->getTimestamp() - $nowParis->getTimestamp());

            return [
                'resetType' => 'event',
                'endsAt' => $eventEndsAt->format(\DateTimeInterface::ATOM),
                'remainingSeconds' => $remaining,
            ];
        }

        if ($kind === 'daily') {
            $resetAt = $nowParis->setTime(0, 0)->modify('+1 day');

            return [
                'resetType' => 'daily',
                'endsAt' => $resetAt->format(\DateTimeInterface::ATOM),
                'remainingSeconds' => max(0, $resetAt->getTimestamp() - $nowParis->getTimestamp()),
            ];
        }

        if ($kind === 'weekly') {
            $dayOfWeek = (int) $nowParis->format('N');
            $daysUntilMonday = 8 - $dayOfWeek;
            if ($daysUntilMonday === 0) {
                $daysUntilMonday = 7;
            }
            $resetAt = $nowParis->setTime(0, 0)->modify("+{$daysUntilMonday} days");

            return [
                'resetType' => 'weekly',
                'endsAt' => $resetAt->format(\DateTimeInterface::ATOM),
                'remainingSeconds' => max(0, $resetAt->getTimestamp() - $nowParis->getTimestamp()),
            ];
        }

        return [
            'resetType' => 'none',
            'endsAt' => null,
            'remainingSeconds' => null,
        ];
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

        $progressPercent = $xpRequiredForLevel > 0
            ? (int) floor(($remainingXp / $xpRequiredForLevel) * 100)
            : 0;

        return [
            'level' => $level,
            'xpIntoLevel' => $remainingXp,
            'xpRequiredForNextLevel' => $xpRequiredForLevel,
            'xpToNextLevel' => $xpRequiredForLevel - $remainingXp,
            'progressPercent' => max(0, min(100, $progressPercent)),
        ];
    }

    private function syncMissingStandardQuestsForUser(User $user): void
    {
        $templates = $this->questTemplateRepository->findActiveStandard();

        foreach ($templates as $template) {
            if (!$template instanceof QuestTemplate) {
                continue;
            }

            $existing = $this->userQuestRepository->findOneForUserAndTemplate($user, $template, null);
            if ($existing instanceof UserQuest) {
                continue;
            }

            $this->entityManager->persist(
                (new UserQuest())
                    ->setUser($user)
                    ->setQuestTemplate($template)
            );
        }
    }
}
