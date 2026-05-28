<?php

namespace App\Service;

use App\Entity\MonsterTemplate;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Repository\MonsterSequenceStepRepository;
use App\Repository\MonsterTemplateRepository;
use App\Repository\UserMonsterRepository;
use Doctrine\ORM\EntityManagerInterface;

final class UserMonsterService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserMonsterRepository $userMonsterRepository,
        private readonly MonsterTemplateRepository $monsterTemplateRepository,
        private readonly MonsterSequenceStepRepository $monsterSequenceStepRepository,
        private readonly LevelService $levelService,
    ) {
    }

    public function spawnInitialMonster(User $user): UserMonster
    {
        return $this->spawnForUser($user);
    }

    public function getOrSpawnActiveMonster(User $user): UserMonster
    {
        $active = $this->userMonsterRepository->findActiveForUser($user);
        if ($active instanceof UserMonster) {
            return $active;
        }

        $monster = $this->spawnForUser($user);
        $this->entityManager->flush();

        return $monster;
    }

    public function spawnForUser(User $user, ?MonsterTemplate $previousTemplate = null): UserMonster
    {
        if ($user->getId() !== null) {
            $this->userMonsterRepository->deactivateAllForUser($user);
        }

        $template = $this->resolveTemplateForSpawn($previousTemplate);
        $maxHp = $template->getBaseHp();

        $monster = (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setMaxHp($maxHp)
            ->setCurrentHp($maxHp)
            ->setIsActive(true);

        $this->entityManager->persist($monster);

        return $monster;
    }

    public function spawnNextInSequence(User $user, MonsterTemplate $deadTemplate): UserMonster
    {
        $next = $this->monsterSequenceStepRepository->findNextTemplateAfter($deadTemplate);

        return $this->spawnForUser($user, $next);
    }

    public function toArray(UserMonster $monster, User $user): array
    {
        $template = $monster->getTemplate();

        return [
            'id' => $monster->getId(),
            'name' => $template->getName(),
            'currentHp' => $monster->getCurrentHp(),
            'maxHp' => $monster->getMaxHp(),
            'level' => $this->levelService->computeLevel($user->getXp()),
            'bossLevel' => $template->getBossLevel(),
            'rarity' => $template->getRarity()->value,
            'affinityStat' => $template->getAffinityStat()->value,
            'imageUrl' => $template->getImageUrl(),
        ];
    }

    private function resolveTemplateForSpawn(?MonsterTemplate $previousTemplate): MonsterTemplate
    {
        if ($previousTemplate instanceof MonsterTemplate) {
            return $previousTemplate;
        }

        $fromSequence = $this->monsterSequenceStepRepository->findFirstTemplate();
        if ($fromSequence instanceof MonsterTemplate) {
            return $fromSequence;
        }

        $playerLevel = $this->levelService->computeLevel(0);
        $candidates = $this->monsterTemplateRepository->findEligibleForLevel($playerLevel, null);
        if ($candidates === []) {
            throw new \RuntimeException('Aucun modèle de monstre disponible pour ce niveau.');
        }

        return $candidates[0];
    }
}

