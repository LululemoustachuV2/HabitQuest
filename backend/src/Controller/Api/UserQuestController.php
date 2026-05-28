<?php

namespace App\Controller\Api;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Entity\QuestReward;
use App\Repository\QuestRewardRepository;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserQuestRepository;
use App\Service\DamagePreviewService;
use App\Service\QuestExpiryService;
use App\Service\QuestProgressService;
use App\Service\QuestRewardStatHelper;
use App\Service\QuestValidationService;
use App\Service\RecurringQuestResetService;
use App\Service\UserMonsterService;
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
        private readonly QuestProgressService $questProgressService,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly QuestRewardRepository $questRewardRepository,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestExpiryService $questExpiryService,
        private readonly RecurringQuestResetService $recurringQuestResetService,
        private readonly EntityManagerInterface $entityManager,
        private readonly DamagePreviewService $damagePreviewService,
        private readonly UserMonsterService $userMonsterService,
    ) {
    }

    #[Route('/api/quests', name: 'api_user_quests_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();

        $this->questValidationService->settleEndedEventRewards($user);
        $this->recurringQuestResetService->resetForUser($user);
        $this->syncMissingStandardQuestsForUser($user);
        $this->questExpiryService->expireInProgressQuestsForUser($user);
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
            $template = $quest->getQuestTemplate();
            $eventEndsAt = $quest->getEvent()?->getEndsAt();
            $timing = $this->buildQuestTiming($quest, $eventEndsAt);
            $hasConditions = $this->questProgressService->templateHasConditions($template);
            $composedReward = $this->questRewardRepository->findOneByQuestTemplate($template);
            $statRewards = $composedReward instanceof QuestReward
                ? QuestRewardStatHelper::formatForApi($composedReward->getParams())
                : [];

            if ($quest->getStatus() === UserQuestStatus::EXPIRED) {
                continue;
            }

            $item = [
                'id' => $quest->getId(),
                'title' => $template->getTitle(),
                'description' => $template->getDescription(),
                'kind' => $template->getKind()->value,
                'xpReward' => $composedReward instanceof QuestReward && $quest->getEvent() === null
                    ? $composedReward->getXp()
                    : $template->getXpReward(),
                'statRewards' => $statRewards,
                'requiredLevel' => $template->getRequiredLevel(),
                'isUnlocked' => $currentLevel >= $template->getRequiredLevel(),
                'isEvent' => $quest->getEvent() !== null,
                'eventId' => $quest->getEvent()?->getId(),
                'eventEndsAt' => $eventEndsAt?->format(\DateTimeInterface::ATOM),
                'eventXpReward' => $quest->getEvent()?->getXpReward(),
                'status' => $quest->getStatus()->value,
                'isValidated' => $quest->isValidated(),
                'hasConditions' => $hasConditions,
                'progress' => $this->questProgressService->formatProgressForApi($quest->getProgress(), $hasConditions),
                'timing' => $quest->getStatus() === UserQuestStatus::IN_PROGRESS ? $timing : [
                    'resetType' => 'none',
                    'endsAt' => null,
                    'remainingSeconds' => null,
                ],
                'damagePreview' => $this->buildDamagePreview($user, $quest),
            ];

            if ($quest->getStatus() === UserQuestStatus::COMPLETED) {
                $response['completed'][] = $item;
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

        if (($result['statusCode'] ?? 0) === Response::HTTP_OK) {
            $user = $this->getUser();
            $this->questProgressService->updateQuestsAfterQuestValidation($user);
        }

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/me/progression', name: 'api_user_progression', methods: ['GET'])]
    public function progression(): JsonResponse
    {
        $user = $this->getUser();

        $this->questValidationService->settleEndedEventRewards($user);
        $this->entityManager->flush();

        $xp = $user->getXp();
        $levelData = $this->computeLevelData($xp);

        return $this->json([
            'xp' => $xp,
            'gold' => $user->getGold(),
            'level' => $levelData['level'],
            'xpIntoLevel' => $levelData['xpIntoLevel'],
            'xpRequiredForNextLevel' => $levelData['xpRequiredForNextLevel'],
            'xpToNextLevel' => $levelData['xpToNextLevel'],
            'progressPercent' => $levelData['progressPercent'],
        ]);
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

    private function buildDamagePreview(User $user, UserQuest $quest): ?array
    {
        if ($quest->getStatus() !== UserQuestStatus::IN_PROGRESS) {
            return null;
        }

        try {
            $monster = $this->userMonsterService->getOrSpawnActiveMonster($user);
        } catch (\RuntimeException) {
            return null;
        }

        return $this->damagePreviewService->preview(
            $user,
            $monster,
            $quest->getQuestTemplate()->getBaseDamage()
        );
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

            $userQuest = (new UserQuest())
                ->setUser($user)
                ->setQuestTemplate($template);

            if ($this->questProgressService->templateHasConditions($template)) {
                $userQuest->setProgress($this->questProgressService->buildInitialProgress($template));
            }

            $this->entityManager->persist($userQuest);
        }
    }
}

