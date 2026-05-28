<?php

namespace App\Tests\Functional\Api;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Enum\BonusStat;
use App\Enum\Rarity;
use App\Enum\UserRole;

final class InventoryApiTest extends ApiTestCase
{
    public function testAnonymousCannotListInventory(): void
    {
        $this->client->jsonRequest('GET', '/api/inventory');

        self::assertResponseStatusCodeSame(401);
    }

    public function testEmptyInventoryReturnsEmptyList(): void
    {
        ['token' => $token] = $this->createAuthenticatedUser('empty');

        $this->client->jsonRequest(
            'GET',
            '/api/inventory',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);
        self::assertSame([], $payload['items']);
        self::assertSame(50, $payload['limit'] ?? null);
        self::assertSame(0, $payload['offset'] ?? null);
    }

    public function testUserSeesOnlyOwnInventory(): void
    {
        ['user' => $owner, 'token' => $ownerToken] = $this->createAuthenticatedUser('owner');

        $ownerItem = $this->createInventoryItem(
            sprintf('__test__inv-owned-%s', uniqid('', true)),
            Rarity::RARE,
            10,
            5,
            BonusStat::FORCE,
            2,
        );
        $otherItem = $this->createInventoryItem(
            sprintf('__test__inv-other-%s', uniqid('', true)),
        );

        $this->grantInventory($owner, $ownerItem);

        $otherEmail = sprintf('__test__inv-other-%s@habitquest.test', uniqid('', true));
        $other = $this->createUser($otherEmail, 'Inv12345!', UserRole::USER);
        $this->grantInventory($other, $otherItem);

        $this->client->jsonRequest(
            'GET',
            '/api/inventory',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $ownerToken)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);
        self::assertCount(1, $payload['items']);

        $entry = $payload['items'][0];
        self::assertSame($ownerItem->getId(), $entry['item']['id']);
        self::assertSame($ownerItem->getName(), $entry['item']['name']);
        self::assertSame('rare', $entry['item']['rarity']);
        self::assertSame(10, $entry['item']['bonusXpPercent']);
        self::assertSame(5, $entry['item']['bonusGold']);
        self::assertSame('force', $entry['item']['bonusStat']);
        self::assertSame(2, $entry['item']['bonusStatValue']);
        self::assertFalse($entry['isEquipped']);
        self::assertArrayHasKey('acquiredAt', $entry);
        self::assertIsString($entry['acquiredAt']);
        self::assertNotSame('', $entry['acquiredAt']);
    }

    public function testMultipleCopiesOfSameItemAreAllowed(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('multi');

        $item = $this->createInventoryItem(sprintf('__test__inv-stack-%s', uniqid('', true)));

        $this->grantInventory($user, $item);
        $this->grantInventory($user, $item);
        $this->grantInventory($user, $item);

        $this->client->jsonRequest(
            'GET',
            '/api/inventory',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(3, $payload['items']);

        foreach ($payload['items'] as $entry) {
            self::assertSame($item->getId(), $entry['item']['id']);
            self::assertFalse($entry['isEquipped']);
        }
    }

    public function testPaginationLimitIsHonoredAndClamped(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('paged');
        $item = $this->createInventoryItem(sprintf('__test__inv-page-%s', uniqid('', true)));

        for ($i = 0; $i < 3; $i++) {
            $this->grantInventory($user, $item);
        }

        $this->client->jsonRequest(
            'GET',
            '/api/inventory?limit=2',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(2, $payload['items']);
        self::assertSame(2, $payload['limit']);

        $this->client->jsonRequest(
            'GET',
            '/api/inventory?limit=9999',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(50, $payload['limit']);
        self::assertLessThanOrEqual(50, count($payload['items']));
        self::assertGreaterThanOrEqual(3, count($payload['items']));
    }

    private function createAuthenticatedUser(string $tag): array
    {
        $email = sprintf('__test__inv-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Inv12345!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Inv12345!');

        return ['user' => $user, 'token' => $token];
    }

    private function createInventoryItem(
        string $name,
        Rarity $rarity = Rarity::COMMON,
        int $bonusXpPercent = 0,
        int $bonusGold = 0,
        ?BonusStat $bonusStat = null,
        int $bonusStatValue = 0,
    ): Item {
        return $this->createItem($name, $rarity, $bonusXpPercent, $bonusGold, $bonusStat, $bonusStatValue);
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

