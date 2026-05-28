<?php

namespace App\Tests\Functional\Api;

use App\Entity\Category;
use App\Entity\Habit;
use App\Entity\HabitLog;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\MonsterTemplate;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Repository\MonsterTemplateRepository;
use App\Enum\Rarity;
use App\Enum\StatType;
use App\Enum\UserRole;
use App\Repository\HabitLogRepository;
use App\Repository\StatRepository;
use App\Repository\UserRepository;

final class HabitLogApiTest extends ApiTestCase
{
    public function testAnonymousCannotLogHabit(): void
    {
        ['user' => $owner] = $this->createAuthenticatedUser('owner-anon');
        $habit = $this->createHabit($owner, 'Habit anonyme');

        $this->client->jsonRequest('POST', sprintf('/api/habits/%d/log', $habit->getId()));

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoggingUnknownHabitReturns404(): void
    {
        ['token' => $token] = $this->createAuthenticatedUser('not-found');

        $this->client->jsonRequest(
            'POST',
            '/api/habits/9999999/log',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testLoggingSomeoneElseHabitReturns422(): void
    {
        ['user' => $owner] = $this->createAuthenticatedUser('owner');
        $habit = $this->createHabit($owner, 'Habit d\'autrui');

        ['token' => $intruderToken] = $this->createAuthenticatedUser('intruder');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $intruderToken)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('habit', $payload['errors']);
    }

    public function testLoggingInactiveHabitReturns422(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('inactive-owner');
        $habit = $this->createHabit($user, 'Habit endormie', isActive: false);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('habit', $payload['errors']);
    }

    public function testLoggingActiveHabitReturnsFullPayloadAndPersistsLog(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('happy-path');
        $this->ensureHighHpMonsterForUser($user);
        $category = $this->createCategory('__test__hl-cat-'.uniqid('', true), StatType::FORCE);
        $habit = $this->createHabit($user, 'Footing 20 min', xpReward: 25, goldReward: 10, category: $category);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            ['note' => 'Sortie en zone 2 ce matin'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        self::assertSame(26, $payload['xpEarned']);
        self::assertSame(10, $payload['goldEarned']);
        self::assertSame(1, $payload['newLevel']);
        self::assertFalse($payload['leveledUp']);
        self::assertSame(25, $payload['monsterDamage']);
        self::assertFalse($payload['monsterDied']);
        self::assertNull($payload['loot']);
        self::assertArrayHasKey('habitLogId', $payload);
        self::assertIsInt($payload['habitLogId']);

        $this->entityManager->clear();
        $logRepo = static::getContainer()->get(HabitLogRepository::class);
        $log = $logRepo->find($payload['habitLogId']);
        self::assertInstanceOf(HabitLog::class, $log);
        self::assertSame(26, $log->getXpEarned());
        self::assertSame(10, $log->getGoldEarned());
        self::assertSame('Sortie en zone 2 ce matin', $log->getNote());

        $userRepo = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepo->find($user->getId());
        self::assertInstanceOf(User::class, $reloadedUser);
        self::assertSame(26, $reloadedUser->getXp());
        self::assertSame(10, $reloadedUser->getGold());
    }

    public function testLoggingHabitIncrementsLinkedStat(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('stat-link');
        $category = $this->createCategory('__test__hl-stat-'.uniqid('', true), StatType::INTELLIGENCE);
        $habit = $this->createHabit($user, 'Apprendre Symfony', xpReward: 25, goldReward: 0, category: $category);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $statRepo = static::getContainer()->get(StatRepository::class);
        $userRepo = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepo->find($user->getId());
        self::assertInstanceOf(User::class, $reloadedUser);

        $stat = $statRepo->findOneByUser($reloadedUser);
        self::assertNotNull($stat);
        self::assertSame(2, $stat->getIntelligence());
        self::assertSame(0, $stat->getForce());
        self::assertSame(0, $stat->getDiscipline());
        self::assertSame(0, $stat->getCreativity());
    }

    public function testLoggingHabitWithoutCategoryDoesNotTouchStats(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('no-cat');
        $habit = $this->createHabit($user, 'Habit orpheline', xpReward: 30, goldReward: 5);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $statRepo = static::getContainer()->get(StatRepository::class);
        $userRepo = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepo->find($user->getId());
        self::assertInstanceOf(User::class, $reloadedUser);

        $stat = $statRepo->findOneByUser($reloadedUser);
        self::assertSame(0, $stat?->getForce() ?? 0);
        self::assertSame(0, $stat?->getIntelligence() ?? 0);
        self::assertSame(0, $stat?->getDiscipline() ?? 0);
        self::assertSame(0, $stat?->getCreativity() ?? 0);

        self::assertSame(31, $reloadedUser->getXp());
        self::assertSame(5, $reloadedUser->getGold());
    }

    public function testLoggingHabitWithSmallXpDoesNotIncrementStat(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('tiny-xp');
        $category = $this->createCategory('__test__hl-tiny-'.uniqid('', true), StatType::DISCIPLINE);
        $habit = $this->createHabit($user, 'Méditer 1 min', xpReward: 5, goldReward: 0, category: $category);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(5, $payload['xpEarned']);

        $this->entityManager->clear();
        $statRepo = static::getContainer()->get(StatRepository::class);
        $userRepo = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepo->find($user->getId());
        self::assertInstanceOf(User::class, $reloadedUser);

        $stat = $statRepo->findOneByUser($reloadedUser);
        self::assertSame(0, $stat?->getDiscipline() ?? 0);
    }

    public function testLoggingHabitReportsLevelUpWhenCrossingThreshold(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('levelup');
        $habit = $this->createHabit($user, 'Lecture intense', xpReward: 100, goldReward: 0);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(105, $payload['xpEarned']);
        self::assertSame(2, $payload['newLevel']);
        self::assertTrue($payload['leveledUp']);
    }

    public function testLevelUpGrantsOnePointInEachStat(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('levelup-stats');
        $habit = $this->createHabit($user, 'Lecture level stats', xpReward: 100, goldReward: 0);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $statRepo = static::getContainer()->get(\App\Repository\StatRepository::class);
        $reloadedUser = static::getContainer()->get(\App\Repository\UserRepository::class)->find($user->getId());
        self::assertNotNull($reloadedUser);

        $stat = $statRepo->findOneByUser($reloadedUser);
        self::assertNotNull($stat);
        self::assertSame(1, $stat->getForce());
        self::assertSame(1, $stat->getIntelligence());
        self::assertSame(1, $stat->getDiscipline());
        self::assertSame(1, $stat->getCreativity());
    }

    public function testLoggingHabitWithEmptyBodyIsAccepted(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('no-body');
        $habit = $this->createHabit($user, 'Habit sans body', xpReward: 10, goldReward: 0);

        $this->client->request(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            server: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(10, $payload['xpEarned']);
    }

    public function testLoggingHabitWithMalformedJsonReturns400(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('bad-json');
        $habit = $this->createHabit($user, 'Habit bad body', xpReward: 10, goldReward: 0);

        $this->client->request(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            server: [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: '{not-json'
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testLoggingHabitWithNoteTypeIntegerReturns422(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('bad-note');
        $habit = $this->createHabit($user, 'Habit note int', xpReward: 10, goldReward: 0);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            ['note' => 12345],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('note', $payload['errors']);
    }

    public function testEquippedItemIncreasesXpByTenPercentOnHabitLog(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('equipped-xp');
        $habit = $this->createHabit($user, 'Habit avec bonus XP', xpReward: 100, goldReward: 0);

        $item = $this->createXpBoostItem(10);
        $entry = $this->grantInventory($user, $item);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $entry->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(115, $payload['xpEarned']);
    }

    public function testLoggingTwoTimesAccumulatesXp(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('accumulate');
        $habit = $this->createHabit($user, 'Habit cumulative', xpReward: 15, goldReward: 3);

        for ($i = 0; $i < 2; ++$i) {
            $this->client->jsonRequest(
                'POST',
                sprintf('/api/habits/%d/log', $habit->getId()),
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
            );
            self::assertResponseStatusCodeSame(201);
        }

        $this->entityManager->clear();
        $userRepo = static::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepo->find($user->getId());
        self::assertInstanceOf(User::class, $reloadedUser);
        self::assertSame(30, $reloadedUser->getXp());
        self::assertSame(6, $reloadedUser->getGold());

        $logRepo = static::getContainer()->get(HabitLogRepository::class);
        self::assertSame(2, $logRepo->countForUser($reloadedUser));
    }

    private function createAuthenticatedUser(string $tag = 'user'): array
    {
        $email = sprintf('__test__habitlog-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Habit1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Habit1234!');

        return ['user' => $user, 'token' => $token];
    }

    private function ensureHighHpMonsterForUser(User $user): void
    {
        $repo = $this->entityManager->getRepository(MonsterTemplate::class);
        $template = $repo->findOneByName('__test__Slime QA');
        self::assertInstanceOf(MonsterTemplate::class, $template);

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
            ->setCurrentHp(500)
            ->setMaxHp(500)
            ->setIsActive(true);

        $this->entityManager->persist($monster);
        $this->entityManager->flush();
    }

    private function createCategory(string $name, StatType $linkedStat = StatType::FORCE): Category
    {
        $category = (new Category())
            ->setName($name)
            ->setLinkedStat($linkedStat);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createHabit(
        User $user,
        string $name,
        int $xpReward = 10,
        int $goldReward = 5,
        bool $isActive = true,
        ?Category $category = null,
    ): Habit {
        $habit = (new Habit())
            ->setUser($user)
            ->setName($name)
            ->setDescription('Habitude de test.')
            ->setXpReward($xpReward)
            ->setGoldReward($goldReward)
            ->setIsActive($isActive);

        if ($category !== null) {
            $habit->setCategory($category);
        }

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return $habit;
    }

    private function createXpBoostItem(int $bonusXpPercent): Item
    {
        $item = (new Item())
            ->setName(sprintf('__test__hl-xp-boost-%s', uniqid('', true)))
            ->setDescription('Item de test +XP.')
            ->setRarity(Rarity::COMMON)
            ->setBonusXpPercent($bonusXpPercent);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    private function grantInventory(User $user, Item $item): Inventory
    {
        $entry = (new Inventory())
            ->setUser($user)
            ->setItem($item);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }
}

