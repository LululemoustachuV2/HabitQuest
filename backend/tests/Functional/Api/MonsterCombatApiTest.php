<?php

namespace App\Tests\Functional\Api;

use App\Entity\Habit;
use App\Entity\Item;
use App\Entity\MonsterTemplate;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Enum\AffinityStat;
use App\Enum\Rarity;
use App\Enum\UserRole;
use App\Repository\InventoryRepository;
use App\Repository\UserMonsterRepository;
final class MonsterCombatApiTest extends ApiTestCase
{
    public function testHabitLogKillsMonsterGrantsLootAndRespawnsStrongerMonster(): void
    {
        $lootItem = $this->createItem(sprintf('__test__combat-loot-%s', uniqid('', true)));
        $template = $this->createWeakMonsterTemplate($lootItem);

        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('combat-kill');
        $this->spawnMonsterWithLowHp($user, $template, currentHp: 10, maxHp: 10);

        $habit = $this->createHighXpHabit($user, xpReward: 50);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertGreaterThanOrEqual(50, $payload['monsterDamage'], 'Dégâts basés sur xpReward habitude (base quête), pas XP gagné.');
        self::assertTrue($payload['monsterDied']);
        self::assertIsArray($payload['loot']);
        self::assertSame($lootItem->getId(), $payload['loot']['item']['id']);

        $this->entityManager->clear();

        $inventoryRepo = static::getContainer()->get(InventoryRepository::class);
        $entries = $inventoryRepo->findAllForUser($user, 50, 0);
        self::assertNotEmpty($entries);

        $monsterRepo = static::getContainer()->get(UserMonsterRepository::class);
        $active = $monsterRepo->findActiveForUser($user);
        self::assertInstanceOf(UserMonster::class, $active);
        self::assertGreaterThan(0, $active->getMaxHp());
        self::assertSame($active->getMaxHp(), $active->getCurrentHp());
        self::assertNotSame($template->getId(), $active->getTemplate()->getId(), 'Respawn V2.2 : boss suivant dans la séquence.');
    }

    public function testAffinityBonusIncreasesDamage(): void
    {
        $lootItem = $this->createItem(sprintf('__test__affinity-loot-%s', uniqid('', true)));
        $template = (new MonsterTemplate())
            ->setName(sprintf('__test__Force fiend %s', uniqid('', true)))
            ->setBaseHp(500)
            ->setLevelMin(1)
            ->setLevelMax(99)
            ->setRarity(Rarity::COMMON)
            ->setAffinityStat(AffinityStat::FORCE)
            ->setLootTable([['itemId' => $lootItem->getId(), 'weight' => 1]]);
        $this->entityManager->persist($template);
        $this->entityManager->flush();
        $this->ensureMonsterInSequence($template);

        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('affinity');
        $this->spawnMonsterWithLowHp($user, $template, currentHp: 500, maxHp: 500);

        $statService = static::getContainer()->get(\App\Service\StatService::class);
        $statService->addStatPoints($user, \App\Enum\StatType::FORCE, 25, \App\Service\StatService::SOURCE_ADMIN_GRANT);

        $habit = $this->createHighXpHabit($user, xpReward: 10);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(41, $payload['monsterDamage']);
    }

    private function createAuthenticatedUser(string $tag): array
    {
        $email = sprintf('__test__monster-combat-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Combat1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Combat1234!');

        return ['user' => $user, 'token' => $token];
    }

    private function createWeakMonsterTemplate(Item $lootItem): MonsterTemplate
    {
        $template = (new MonsterTemplate())
            ->setName(sprintf('__test__Weak mob %s', uniqid('', true)))
            ->setBaseHp(10)
            ->setLevelMin(1)
            ->setLevelMax(99)
            ->setRarity(Rarity::COMMON)
            ->setAffinityStat(AffinityStat::NEUTRAL)
            ->setLootTable([['itemId' => $lootItem->getId(), 'weight' => 100]]);

        $this->entityManager->persist($template);
        $this->entityManager->flush();
        $this->ensureMonsterInSequence($template);

        return $template;
    }

    private function spawnMonsterWithLowHp(User $user, MonsterTemplate $template, int $currentHp, int $maxHp): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(UserMonster::class, 'um')
            ->set('um.isActive', ':inactive')
            ->where('um.user = :user')
            ->setParameter('inactive', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        $monster = (new UserMonster())
            ->setUser($user)
            ->setTemplate($template)
            ->setCurrentHp($currentHp)
            ->setMaxHp($maxHp)
            ->setIsActive(true);

        $this->entityManager->persist($monster);
        $this->entityManager->flush();
    }

    private function createHighXpHabit(User $user, int $xpReward): Habit
    {
        $habit = (new Habit())
            ->setUser($user)
            ->setName('Coup fatal')
            ->setDescription('Test combat')
            ->setXpReward($xpReward)
            ->setGoldReward(0)
            ->setIsActive(true);

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return $habit;
    }
}

