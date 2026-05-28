<?php

namespace App\Tests\Functional\Api;

use App\Entity\Item;
use App\Entity\User;
use App\Enum\Rarity;
use App\Enum\UserRole;
use App\Repository\InventoryRepository;
final class ShopApiTest extends ApiTestCase
{
    public function testAnonymousCannotAccessShop(): void
    {
        $this->client->jsonRequest('GET', '/api/shop');
        self::assertResponseStatusCodeSame(401);
    }

    public function testUserCanListShopRotation(): void
    {
        $this->seedSellableItems(6);
        ['token' => $token] = $this->createAuthenticatedUser('shop-list');

        $this->client->jsonRequest(
            'GET',
            '/api/shop',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('rotationDate', $payload);
        self::assertArrayHasKey('items', $payload);
        self::assertGreaterThanOrEqual(4, count($payload['items']));
        self::assertLessThanOrEqual(6, count($payload['items']));
        self::assertArrayHasKey('shopPrice', $payload['items'][0]);
    }

    public function testPurchaseDeductsGoldAndGrantsInventory(): void
    {
        $item = $this->createItem(
            sprintf('__test__shop-buy-%s', uniqid('', true)),
            isSellable: true,
            shopPrice: 30,
        );
        $this->forceSingleItemRotation($item);

        $email = sprintf('__test__shop-buyer-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Shop1234!', UserRole::USER);
        $userRef = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $userRef);
        $userRef->addGold(100);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Shop1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/shop/purchase/%d', $item->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(70, $payload['gold'] ?? null);

        $this->entityManager->clear();
        $refreshed = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $refreshed);
        self::assertSame(70, $refreshed->getGold());

        $inventory = self::getContainer()->get(InventoryRepository::class)->findBy(['user' => $refreshed]);
        self::assertNotEmpty($inventory);
    }

    public function testPurchasedItemStaysInShopListWithPurchasedFlag(): void
    {
        $item = $this->createItem(
            sprintf('__test__shop-stay-%s', uniqid('', true)),
            isSellable: true,
            shopPrice: 25,
        );
        $this->forceSingleItemRotation($item);

        $email = sprintf('__test__shop-stay-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Shop1234!', UserRole::USER);
        $userRef = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $userRef);
        $userRef->addGold(100);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Shop1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/shop/purchase/%d', $item->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();

        $this->client->jsonRequest(
            'GET',
            '/api/shop',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $row = null;
        foreach ($payload['items'] ?? [] as $shopItem) {
            if ((int) $shopItem['id'] === (int) $item->getId()) {
                $row = $shopItem;
                break;
            }
        }
        self::assertNotNull($row);
        self::assertTrue($row['purchased'] ?? false);
    }

    public function testPurchaseFailsWhenNotEnoughGold(): void
    {
        $item = $this->createItem(
            sprintf('__test__shop-poor-%s', uniqid('', true)),
            isSellable: true,
            shopPrice: 500,
        );
        $this->forceSingleItemRotation($item);

        ['token' => $token] = $this->createAuthenticatedUser('shop-poor');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/shop/purchase/%d', $item->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testPurchaseFailsWhenItemNotInRotation(): void
    {
        $inRotation = $this->createItem(
            sprintf('__test__shop-rot-%s', uniqid('', true)),
            isSellable: true,
            shopPrice: 10,
        );
        $notInRotation = $this->createItem(
            sprintf('__test__shop-norot-%s', uniqid('', true)),
            isSellable: true,
            shopPrice: 10,
        );
        $this->forceSingleItemRotation($inRotation);

        ['token' => $token] = $this->createAuthenticatedUser('shop-norot');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/shop/purchase/%d', $notInRotation->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    private function createAuthenticatedUser(string $tag): array
    {
        $email = sprintf('__test__shop-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Shop12345!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Shop12345!');

        return ['user' => $user, 'token' => $token];
    }

    private function seedSellableItems(int $count): array
    {
        $items = [];
        for ($i = 0; $i < $count; ++$i) {
            $items[] = $this->createItem(
                sprintf('__test__shop-item-%d-%s', $i, uniqid('', true)),
                Rarity::COMMON,
                isSellable: true,
                shopPrice: 10 + $i,
            );
        }

        return $items;
    }

    private function forceSingleItemRotation(Item $item): void
    {
        foreach ($this->entityManager->getRepository(Item::class)->findSellableWithPrice() as $sellable) {
            if ($sellable->getId() !== $item->getId()) {
                $sellable->setIsSellable(false);
            }
        }
        $item->setIsSellable(true)->setShopPrice($item->getShopPrice() ?? 10);
        $this->entityManager->flush();
    }
}

