<?php

namespace App\Service;

use App\Entity\Habit;
use App\Entity\HabitLog;
use App\Entity\User;
use App\Enum\StatType;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

final class HabitLogService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly XpService $xpService,
        private readonly EventMultiplierService $eventMultiplierService,
        private readonly LevelService $levelService,
        private readonly StatService $statService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly UserMonsterService $userMonsterService,
        private readonly CombatService $combatService,
        private readonly MonsterService $monsterService,
        private readonly QuestProgressService $questProgressService,
        private readonly StreakService $streakService,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function logHabit(Habit $habit, User $user, ?string $note = null): array
    {
        if ($habit->getUser()->getId() !== $user->getId()) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Validation échouée.',
                'errors' => ['habit' => 'Habitude non détenue par l\'utilisateur.'],
            ];
        }

        if (!$habit->isActive()) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Validation échouée.',
                'errors' => ['habit' => 'Habitude inactive : aucune exécution ne peut être enregistrée.'],
            ];
        }

        $this->streakService->updateOnHabitLog($user);

        $xpEarned = $this->xpService->calculate($habit, $user);
        $goldEarned = max(0, $habit->getGoldReward()) + $this->sumEquippedBonusGold($user);

        $xpEarned = $this->eventMultiplierService->applyXp($xpEarned);
        $goldEarned = $this->eventMultiplierService->applyGold($goldEarned);

        $previousXp = $user->getXp();

        if ($goldEarned > 0) {
            $user->addGold($goldEarned);
        }
        if ($xpEarned > 0) {
            $user->addXp($xpEarned);
        }

        $note = $this->normalizeNote($note);
        $habitLog = new HabitLog(
            habit: $habit,
            user: $user,
            xpEarned: $xpEarned,
            goldEarned: $goldEarned,
            note: $note,
        );
        $this->entityManager->persist($habitLog);

        $statIncrement = 0;
        $linkedStat = null;
        if ($habit->getCategory() !== null && $xpEarned > 0) {
            $linkedStat = $habit->getCategory()->getLinkedStat();
            $statIncrement = intdiv($xpEarned, 10);
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

        if ($statIncrement > 0 && $linkedStat !== null) {
            $this->statService->addStatPoints(
                $user,
                $linkedStat,
                $statIncrement,
                StatService::SOURCE_HABIT_LOG
            );
        } else {
            $this->entityManager->flush();
        }

        $this->questProgressService->updateQuestsAfterHabitLog($user, $habitLog);

        $this->entityManager->flush();

        $this->applyEquippedItemStatBonuses($user);

        $combatPayload = $this->applyCombat($user, max(0, $habit->getXpReward()));

        $this->logger->info('PMVP-005 — HabitLog créé.', [
            'habitLogId' => $habitLog->getId(),
            'habitId' => $habit->getId(),
            'userId' => $user->getId(),
            'xpEarned' => $xpEarned,
            'goldEarned' => $goldEarned,
            'statIncrement' => $statIncrement,
            'linkedStat' => $linkedStat?->value,
            'leveledUp' => $levelInfo['leveledUp'],
            'newLevel' => $levelInfo['newLevel'],
            'monsterDamage' => $combatPayload['monsterDamage'],
            'monsterDied' => $combatPayload['monsterDied'],
        ]);

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Habitude enregistrée.',
            'habitLogId' => $habitLog->getId(),
            'xpEarned' => $xpEarned,
            'goldEarned' => $goldEarned,
            'newLevel' => $levelInfo['newLevel'],
            'leveledUp' => $levelInfo['leveledUp'],
            'monsterDamage' => $combatPayload['monsterDamage'],
            'monsterDied' => $combatPayload['monsterDied'],
            'loot' => $combatPayload['loot'],
        ];
    }

    private function applyCombat(User $user, int $questBaseDamage): array
    {
        try {
            $monster = $this->userMonsterService->getOrSpawnActiveMonster($user);
        } catch (\RuntimeException) {
            return [
                'monsterDamage' => 0,
                'monsterDied' => false,
                'loot' => null,
            ];
        }

        $combatResult = $this->combatService->applyDamageFromQuestBase(
            $user,
            $monster,
            $questBaseDamage
        );
        $loot = null;

        if ($combatResult['monsterDied']) {
            $deathResult = $this->monsterService->onMonsterDeath($user, $monster);
            $loot = $deathResult['loot'];
        }

        $this->entityManager->flush();

        return [
            'monsterDamage' => $combatResult['damage'],
            'monsterDied' => $combatResult['monsterDied'],
            'loot' => $loot,
        ];
    }

    private function sumEquippedBonusGold(User $user): int
    {
        $sum = 0;
        foreach ($this->inventoryRepository->findEquippedForUser($user) as $entry) {
            $sum += $entry->getItem()->getBonusGold();
        }

        return max(0, $sum);
    }

    private function applyEquippedItemStatBonuses(User $user): void
    {
        foreach ($this->inventoryRepository->findEquippedForUser($user) as $entry) {
            $item = $entry->getItem();
            $bonusStat = $item->getBonusStat();
            $bonusStatValue = $item->getBonusStatValue();

            if ($bonusStat === null || $bonusStatValue <= 0) {
                continue;
            }

            $this->statService->addStatPoints(
                $user,
                StatType::from($bonusStat->value),
                $bonusStatValue,
                StatService::SOURCE_ITEM_EQUIPPED
            );
        }
    }

    private function normalizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $trimmed = trim($note);
        if ($trimmed === '') {
            return null;
        }

        if (mb_strlen($trimmed) > 2000) {
            $trimmed = mb_substr($trimmed, 0, 2000);
        }

        return $trimmed;
    }
}

