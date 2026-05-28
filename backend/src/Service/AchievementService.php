<?php

namespace App\Service;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Enum\AchievementCode;
use App\Repository\AchievementRepository;
use App\Repository\UserAchievementRepository;
use App\Repository\UserMonsterRepository;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AchievementService
{
    private const IRON_DISCIPLINE_QUEST_THRESHOLD = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AchievementRepository $achievementRepository,
        private readonly UserAchievementRepository $userAchievementRepository,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly UserMonsterRepository $userMonsterRepository,
    ) {
    }

    public function checkAfterQuestValidated(User $user): array
    {
        $validatedCount = $this->userQuestRepository->countValidatedForUser($user);
        $unlocked = [];

        if ($validatedCount === 1) {
            $code = $this->tryUnlock($user, AchievementCode::FIRST_QUEST_VALIDATED);
            if ($code !== null) {
                $unlocked[] = $code;
            }
        }

        if ($validatedCount >= self::IRON_DISCIPLINE_QUEST_THRESHOLD) {
            $code = $this->tryUnlock($user, AchievementCode::IRON_DISCIPLINE);
            if ($code !== null) {
                $unlocked[] = $code;
            }
        }

        return $unlocked;
    }

    public function checkAfterMonsterDeath(User $user): array
    {
        if ($this->userMonsterRepository->countDefeatedForUser($user) < 1) {
            return [];
        }

        $code = $this->tryUnlock($user, AchievementCode::FIRST_MONSTER_KILL);

        return $code !== null ? [$code] : [];
    }

    public function listForUser(User $user): array
    {
        $unlockedByCode = [];
        foreach ($this->userAchievementRepository->findAllForUser($user) as $userAchievement) {
            $unlockedByCode[$userAchievement->getAchievement()->getCode()->value] = $userAchievement;
        }

        $payload = [];
        foreach ($this->achievementRepository->findAllOrdered() as $achievement) {
            $code = $achievement->getCode()->value;
            $userAchievement = $unlockedByCode[$code] ?? null;

            $payload[] = [
                'id' => $achievement->getId(),
                'code' => $code,
                'name' => $achievement->getName(),
                'description' => $achievement->getDescription(),
                'unlocked' => $userAchievement !== null,
                'unlockedAt' => $userAchievement?->getUnlockedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $payload;
    }

    private function tryUnlock(User $user, AchievementCode $code): ?string
    {
        if ($this->userAchievementRepository->hasUnlockedCode($user, $code)) {
            return null;
        }

        $achievement = $this->achievementRepository->findOneByCode($code);
        if (!$achievement instanceof Achievement) {
            return null;
        }

        $this->entityManager->persist(new UserAchievement($user, $achievement));

        return $code->value;
    }
}

