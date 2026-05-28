<?php

namespace App\Tests\Functional\Api;

use App\Entity\UserMonster;
use App\Enum\UserRole;
use App\Repository\UserMonsterRepository;

final class MonsterApiTest extends ApiTestCase
{
    public function testRegisterSpawnsInitialMonster(): void
    {
        $email = sprintf('__test__monster-register-%s@habitquest.test', uniqid('', true));

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => 'Monster1234!',
        ]);

        self::assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);

        $repo = static::getContainer()->get(UserMonsterRepository::class);
        $active = $repo->findActiveForUser($user);
        self::assertInstanceOf(UserMonster::class, $active);
        self::assertTrue($active->isActive());
        self::assertGreaterThan(0, $active->getMaxHp());
    }

    public function testGetActiveMonsterLazySpawnsForLegacyUser(): void
    {
        $email = sprintf('__test__monster-legacy-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Monster1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Monster1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/monster/active',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('monster', $payload);
        self::assertArrayHasKey('currentHp', $payload['monster']);
        self::assertArrayHasKey('maxHp', $payload['monster']);
        self::assertSame(1, $payload['monster']['level']);

        $repo = static::getContainer()->get(UserMonsterRepository::class);
        self::assertInstanceOf(UserMonster::class, $repo->findActiveForUser($user));
    }

    public function testAnonymousCannotGetActiveMonster(): void
    {
        $this->client->jsonRequest('GET', '/api/monster/active');
        self::assertResponseStatusCodeSame(401);
    }
}

