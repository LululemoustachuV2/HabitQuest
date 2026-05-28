<?php

namespace App\Tests\Functional\Api;

use App\Entity\AdminAuditLog;
use App\Entity\User;
use App\Entity\UserMonster;
use App\Enum\UserRole;
use App\Repository\AdminAuditLogRepository;
use App\Repository\UserMonsterRepository;
use App\Service\AdminAuditLogService;
use App\Service\UserMonsterService;

final class AdminUserModerationApiTest extends ApiTestCase
{
    public function testUserCannotGrantXp(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/1/grant-xp',
            ['amount' => 50, 'reason' => 'Test modération'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testUserCannotGrantGold(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/1/grant-gold',
            ['amount' => 10, 'reason' => 'Test modération'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testUserCannotRespawnMonster(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/1/respawn-monster',
            ['reason' => 'Support joueur'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminGrantXpRequiresReason(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $target = $this->createUser(
            sprintf('__test__target-%s@habitquest.test', uniqid('', true)),
            'User1234!',
            UserRole::USER
        );
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/users/%d/grant-xp', $target->getId()),
            ['amount' => 25],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminCanGrantXpAndGoldWithAudit(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $admin = $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $target = $this->createUser(
            sprintf('__test__target-%s@habitquest.test', uniqid('', true)),
            'User1234!',
            UserRole::USER
        );
        $target->addXp(10)->addGold(5);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $targetId = $target->getId();
        self::assertNotNull($targetId);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/users/%d/grant-xp', $targetId),
            ['amount' => 40, 'reason' => 'Compensation bug combat'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(200);
        $xpPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(50, $xpPayload['user']['xp']);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/users/%d/grant-gold', $targetId),
            ['amount' => 15, 'reason' => 'Bonus event manqué'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(200);
        $goldPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(20, $goldPayload['user']['gold']);

        $auditRepo = static::getContainer()->get(AdminAuditLogRepository::class);
        $logs = $auditRepo->findBy(['adminUser' => $admin], ['id' => 'ASC']);
        self::assertCount(2, $logs);
        self::assertSame(AdminAuditLogService::ACTION_GRANT_XP, $logs[0]->getAction());
        self::assertSame(sprintf('user:%d', $targetId), $logs[0]->getTarget());
        self::assertSame('Compensation bug combat', $logs[0]->getPayload()['reason']);
        self::assertSame(AdminAuditLogService::ACTION_GRANT_GOLD, $logs[1]->getAction());
    }

    public function testAdminCanRespawnMonsterWithAudit(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $admin = $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $target = $this->createUser(
            sprintf('__test__target-%s@habitquest.test', uniqid('', true)),
            'User1234!',
            UserRole::USER
        );

        $monsterService = static::getContainer()->get(UserMonsterService::class);
        $initial = $monsterService->spawnInitialMonster($target);
        $this->entityManager->flush();
        $initialId = $initial->getId();
        self::assertNotNull($initialId);

        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $targetId = $target->getId();
        self::assertNotNull($targetId);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/users/%d/respawn-monster', $targetId),
            ['reason' => 'Monstre bloqué en support'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(200);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('monster', $payload);
        self::assertNotSame($initialId, $payload['monster']['id']);

        $userMonsterRepo = static::getContainer()->get(UserMonsterRepository::class);
        $oldMonster = $userMonsterRepo->find($initialId);
        self::assertInstanceOf(UserMonster::class, $oldMonster);
        self::assertFalse($oldMonster->isActive());

        $active = $userMonsterRepo->findActiveForUser($target);
        self::assertInstanceOf(UserMonster::class, $active);
        self::assertTrue($active->isActive());

        $auditRepo = static::getContainer()->get(AdminAuditLogRepository::class);
        $log = $auditRepo->findOneBy(
            ['adminUser' => $admin, 'action' => AdminAuditLogService::ACTION_RESPAWN_MONSTER],
            ['id' => 'DESC']
        );
        self::assertInstanceOf(AdminAuditLog::class, $log);
        self::assertSame('Monstre bloqué en support', $log->getPayload()['reason']);
    }

    public function testAdminGrantReturns404ForUnknownUser(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/users/999999/grant-xp',
            ['amount' => 10, 'reason' => 'Test utilisateur absent'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(404);
    }
}

