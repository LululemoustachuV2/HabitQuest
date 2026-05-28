<?php

namespace App\Tests\Functional\Api;

use App\Entity\Stat;
use App\Entity\User;
use App\Enum\StatType;
use App\Enum\UserRole;
use App\Repository\StatRepository;
use App\Repository\UserRepository;
use App\Service\StatService;

final class ApiStatsTest extends ApiTestCase
{
    public function testRegisterCreatesStatRowForNewUser(): void
    {
        $email = sprintf('__test__stats-register-%s@habitquest.test', uniqid('', true));

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => 'Stats1234!',
        ]);
        self::assertResponseStatusCodeSame(201);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $statRepo = static::getContainer()->get(StatRepository::class);

        $user = $userRepo->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        $stat = $statRepo->findOneByUser($user);
        self::assertInstanceOf(Stat::class, $stat, 'Un nouvel utilisateur doit recevoir une ligne stats à l\'inscription.');
        self::assertSame(0, $stat->getForce());
        self::assertSame(0, $stat->getIntelligence());
        self::assertSame(0, $stat->getDiscipline());
        self::assertSame(0, $stat->getCreativity());
    }

    public function testGetUserStatsReturnsRealStatPayload(): void
    {
        $email = sprintf('__test__stats-get-%s@habitquest.test', uniqid('', true));
        $password = 'Stats1234!';

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => $password,
        ]);
        self::assertResponseStatusCodeSame(201);

        $token = $this->authenticate($this->client, $email, $password);

        $this->client->jsonRequest(
            'GET',
            '/api/user/stats',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        self::assertSame(1, $payload['level']);
        self::assertSame(0, $payload['xp']);
        self::assertSame(0, $payload['gold']);

        self::assertIsArray($payload['stats']);
        self::assertSame(
            ['force' => 0, 'intelligence' => 0, 'discipline' => 0, 'creativity' => 0],
            $payload['stats']
        );
    }

    public function testGetUserStatsReflectsStatServiceAddPoints(): void
    {
        $email = sprintf('__test__stats-add-%s@habitquest.test', uniqid('', true));
        $password = 'Stats1234!';

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => $password,
        ]);
        self::assertResponseStatusCodeSame(201);

        $userRepo = static::getContainer()->get(UserRepository::class);
        $statService = static::getContainer()->get(StatService::class);

        $user = $userRepo->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        $statService->addStatPoints($user, StatType::FORCE, 4, StatService::SOURCE_HABIT_LOG);
        $statService->addStatPoints($user, StatType::INTELLIGENCE, 2, StatService::SOURCE_QUEST_REWARD);
        $statService->addStatPoints($user, StatType::INTELLIGENCE, 1, StatService::SOURCE_ITEM_EQUIPPED);
        $statService->addStatPoints($user, StatType::DISCIPLINE, 5, StatService::SOURCE_EVENT);
        $statService->addStatPoints($user, StatType::CREATIVITY, 0, StatService::SOURCE_ACHIEVEMENT);

        $token = $this->authenticate($this->client, $email, $password);

        $this->client->jsonRequest(
            'GET',
            '/api/user/stats',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        self::assertSame(
            ['force' => 4, 'intelligence' => 3, 'discipline' => 5, 'creativity' => 0],
            $payload['stats']
        );
    }

    public function testGetUserStatsAutoCreatesStatForLegacyUserWithoutBackfill(): void
    {
        $email = sprintf('__test__stats-legacy-%s@habitquest.test', uniqid('', true));
        $password = 'Stats1234!';
        $user = $this->createUser($email, $password, UserRole::USER);

        $statRepo = static::getContainer()->get(StatRepository::class);
        if ($statRepo->findOneByUser($user) instanceof Stat) {
            self::markTestSkipped('Le user legacy a déjà une Stat — scénario non couvert ici.');
        }

        $token = $this->authenticate($this->client, $email, $password);

        $this->client->jsonRequest(
            'GET',
            '/api/user/stats',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(
            ['force' => 0, 'intelligence' => 0, 'discipline' => 0, 'creativity' => 0],
            $payload['stats']
        );

        $this->entityManager->clear();
        $userRefetched = static::getContainer()->get(UserRepository::class)->findOneByEmail($email);
        self::assertInstanceOf(User::class, $userRefetched);
        self::assertInstanceOf(
            Stat::class,
            static::getContainer()->get(StatRepository::class)->findOneByUser($userRefetched),
            'Une Stat doit avoir été auto-créée à la volée pour le user legacy.'
        );
    }

    public function testGetUserStatsRequiresAuthentication(): void
    {
        $this->client->jsonRequest('GET', '/api/user/stats');

        self::assertResponseStatusCodeSame(401);
    }
}

