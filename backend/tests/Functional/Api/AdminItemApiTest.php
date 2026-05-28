<?php

namespace App\Tests\Functional\Api;

use App\Entity\Item;
use App\Enum\UserRole;

final class AdminItemApiTest extends ApiTestCase
{
    public function testUserCannotAccessAdminItemsEndpoint(): void
    {
        $email = sprintf('__test__item-user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/admin/items',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousCannotAccessAdminItemsEndpoint(): void
    {
        $this->client->jsonRequest('GET', '/api/admin/items');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanCreateItem(): void
    {
        $token = $this->authenticateAdmin();
        $name = sprintf('__test__Épée %s', uniqid('', true));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => $name,
                'description' => 'Une épée de test pour PMVP-012.',
                'rarity' => 'rare',
                'bonusXpPercent' => 15,
                'bonusGold' => 5,
                'bonusStat' => 'force',
                'bonusStatValue' => 2,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(201, $payload['statusCode'] ?? null);
        self::assertArrayHasKey('item', $payload);
        self::assertSame($name, $payload['item']['name']);
        self::assertSame('rare', $payload['item']['rarity']);
        self::assertSame(15, $payload['item']['bonusXpPercent']);
        self::assertSame(5, $payload['item']['bonusGold']);
        self::assertSame('force', $payload['item']['bonusStat']);
        self::assertSame(2, $payload['item']['bonusStatValue']);
        self::assertSame(0, $payload['item']['bonusEquipSlots']);
        self::assertIsInt($payload['item']['id']);
    }

    public function testAdminCanCreateItemWithoutBonusStat(): void
    {
        $token = $this->authenticateAdmin();
        $name = sprintf('__test__Cape %s', uniqid('', true));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => $name,
                'description' => 'Cape commune sans stat liée.',
                'rarity' => 'common',
                'bonusXpPercent' => 0,
                'bonusGold' => 3,
                'bonusStat' => null,
                'bonusStatValue' => 0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertNull($payload['item']['bonusStat']);
    }

    public function testAdminCannotCreateItemWithInvalidRarity(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => sprintf('__test__Invalide %s', uniqid('', true)),
                'description' => 'Rareté invalide.',
                'rarity' => 'legendary',
                'bonusXpPercent' => 0,
                'bonusGold' => 0,
                'bonusStatValue' => 0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('rarity', $payload['errors']);
    }

    public function testAdminCannotCreateItemWithInvalidBonusXp(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => sprintf('__test__Overpower %s', uniqid('', true)),
                'description' => 'Bonus XP hors plage.',
                'rarity' => 'common',
                'bonusXpPercent' => 150,
                'bonusGold' => 0,
                'bonusStatValue' => 0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('bonusXpPercent', $payload['errors']);
    }

    public function testAdminCannotCreateItemWithInvalidBonusStat(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => sprintf('__test__BadStat %s', uniqid('', true)),
                'description' => 'Stat inexistante.',
                'rarity' => 'common',
                'bonusXpPercent' => 0,
                'bonusGold' => 0,
                'bonusStat' => 'charisma',
                'bonusStatValue' => 1,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('bonusStat', $payload['errors']);
    }

    public function testAdminCanListItems(): void
    {
        $token = $this->authenticateAdmin();
        $this->createItem(sprintf('__test__Liste %s', uniqid('', true)));

        $this->client->jsonRequest(
            'GET',
            '/api/admin/items',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);
        self::assertNotEmpty($payload['items']);
    }

    public function testAdminCanUpdateItem(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__ToUpdate %s', uniqid('', true)));

        $this->client->jsonRequest(
            'PUT',
            sprintf('/api/admin/items/%d', $item->getId()),
            [
                'name' => 'Item modifié',
                'description' => 'Description mise à jour.',
                'rarity' => 'epic',
                'bonusXpPercent' => 30,
                'bonusGold' => 50,
                'bonusStat' => 'creativity',
                'bonusStatValue' => 5,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('epic', $payload['item']['rarity']);
        self::assertSame(30, $payload['item']['bonusXpPercent']);
        self::assertSame('creativity', $payload['item']['bonusStat']);
    }

    public function testAdminCannotUpdateMissingItem(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'PUT',
            '/api/admin/items/9999999',
            [
                'name' => 'Inexistant',
                'description' => 'Item inexistant.',
                'rarity' => 'common',
                'bonusXpPercent' => 0,
                'bonusGold' => 0,
                'bonusStatValue' => 0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanDeleteItem(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__ToDelete %s', uniqid('', true)));
        $id = $item->getId();

        $this->client->jsonRequest(
            'DELETE',
            sprintf('/api/admin/items/%d', $id),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Item::class)->find($id));
    }

    public function testAdminCanShowItem(): void
    {
        $token = $this->authenticateAdmin();
        $item = $this->createItem(sprintf('__test__Show %s', uniqid('', true)));

        $this->client->jsonRequest(
            'GET',
            sprintf('/api/admin/items/%d', $item->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame($item->getId(), $payload['item']['id']);
    }

    public function testAdminCanCreateSellableItemWithShopPrice(): void
    {
        $token = $this->authenticateAdmin();
        $name = sprintf('__test__ShopItem %s', uniqid('', true));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => $name,
                'description' => 'Item boutique.',
                'rarity' => 'common',
                'bonusXpPercent' => 0,
                'bonusGold' => 0,
                'bonusStatValue' => 0,
                'bonusEquipSlots' => 0,
                'isSellable' => true,
                'shopPrice' => 55,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['item']['isSellable']);
        self::assertSame(55, $payload['item']['shopPrice']);
    }

    public function testAdminCannotCreateSellableItemWithoutShopPrice(): void
    {
        $token = $this->authenticateAdmin();

        $this->client->jsonRequest(
            'POST',
            '/api/admin/items',
            [
                'name' => sprintf('__test__NoPrice %s', uniqid('', true)),
                'description' => 'Vendable sans prix.',
                'rarity' => 'common',
                'bonusXpPercent' => 0,
                'bonusGold' => 0,
                'bonusStatValue' => 0,
                'bonusEquipSlots' => 0,
                'isSellable' => true,
                'shopPrice' => null,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('shopPrice', $payload['errors']);
    }

    private function authenticateAdmin(): string
    {
        $email = sprintf('__test__item-admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'Admin1234!', UserRole::ADMIN);

        return $this->authenticate($this->client, $email, 'Admin1234!');
    }
}

