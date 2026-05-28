<?php

namespace App\Tests\Functional\Api;

use App\Entity\Item;
use App\Entity\MonsterTemplate;
use App\Enum\UserRole;

final class AdminMonsterApiTest extends ApiTestCase
{
    public function testUserCannotAccessAdminMonstersEndpoint(): void
    {
        $email = sprintf('__test__monster-user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/admin/monsters',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateMonsterTemplate(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__loot-monster-%s', uniqid('', true)));
        $name = sprintf('__test__Dragon %s', uniqid('', true));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/monsters',
            [
                'name' => $name,
                'baseHp' => 200,
                'levelMin' => 1,
                'levelMax' => 10,
                'rarity' => 'epic',
                'affinityStat' => 'force',
                'lootTable' => [['itemId' => $item->getId(), 'weight' => 50]],
                'imageUrl' => 'https://example.com/dragon.png',
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame($name, $payload['monster']['name']);
        self::assertSame('force', $payload['monster']['affinityStat']);
        self::assertSame(200, $payload['monster']['baseHp']);
    }

    public function testAdminCannotCreateMonsterWithInvalidLevelRange(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__loot-invalid-%s', uniqid('', true)));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/monsters',
            [
                'name' => sprintf('__test__BadLevel %s', uniqid('', true)),
                'baseHp' => 50,
                'levelMin' => 10,
                'levelMax' => 2,
                'rarity' => 'common',
                'affinityStat' => 'neutral',
                'lootTable' => [['itemId' => $item->getId(), 'weight' => 1]],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('levelMin', $payload['errors']);
    }

    public function testAdminCanListMonsters(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'GET',
            '/api/admin/monsters',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('monsters', $payload);
        self::assertNotEmpty($payload['monsters']);
    }

    public function testAdminCanDeleteMonster(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__loot-del-%s', uniqid('', true)));
        $monster = $this->createMonsterTemplate('__test__ToDelete', $item);

        $this->client->jsonRequest(
            'DELETE',
            sprintf('/api/admin/monsters/%d', $monster->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(MonsterTemplate::class)->find($monster->getId()));
    }

    private function authenticateAdmin(): string
    {
        $email = sprintf('__test__monster-admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'Admin1234!', UserRole::ADMIN);

        return $this->authenticate($this->client, $email, 'Admin1234!');
    }

    private function createMonsterTemplate(string $name, Item $lootItem): MonsterTemplate
    {
        $template = (new MonsterTemplate())
            ->setName($name)
            ->setBaseHp(80)
            ->setLevelMin(1)
            ->setLevelMax(99)
            ->setLootTable([['itemId' => $lootItem->getId(), 'weight' => 10]]);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }
}

