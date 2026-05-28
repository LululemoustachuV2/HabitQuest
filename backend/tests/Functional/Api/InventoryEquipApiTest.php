<?php

namespace App\Tests\Functional\Api;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Enum\Rarity;
use App\Enum\UserRole;
use App\Service\InventoryService;

final class InventoryEquipApiTest extends ApiTestCase
{
    public function testAnonymousCannotEquip(): void
    {
        $this->client->jsonRequest('POST', '/api/inventory/1/equip');

        self::assertResponseStatusCodeSame(401);
    }

    public function testEquipUnknownEntryReturns404(): void
    {
        ['token' => $token] = $this->createAuthenticatedUser('not-found');

        $this->client->jsonRequest(
            'POST',
            '/api/inventory/9999999/equip',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testEquipSomeoneElseInventoryReturns403(): void
    {
        ['user' => $owner] = $this->createAuthenticatedUser('owner');
        $item = $this->createItem(sprintf('__test__eq-own-%s', uniqid('', true)));
        $ownerEntry = $this->grantInventory($owner, $item);

        ['token' => $intruderToken] = $this->createAuthenticatedUser('intruder');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $ownerEntry->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $intruderToken)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testEquipTogglesEquippedFlag(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('toggle');
        $item = $this->createItem(sprintf('__test__eq-toggle-%s', uniqid('', true)));
        $entry = $this->grantInventory($user, $item);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $entry->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['isEquipped']);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $entry->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertFalse($payload['isEquipped']);
    }

    public function testUpToThreeItemsCanBeEquipped(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('multi-slot');
        $entries = [];
        for ($i = 0; $i < 3; ++$i) {
            $item = $this->createItem(sprintf('__test__eq-slot-%d-%s', $i, uniqid('', true)));
            $entries[] = $this->grantInventory($user, $item);
        }

        foreach ($entries as $entry) {
            $this->client->jsonRequest(
                'POST',
                sprintf('/api/inventory/%d/equip', $entry->getId()),
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
            );
            self::assertResponseIsSuccessful();
        }

        $this->entityManager->clear();
        foreach ($entries as $entry) {
            $reloaded = $this->entityManager->find(Inventory::class, $entry->getId());
            self::assertInstanceOf(Inventory::class, $reloaded);
            self::assertTrue($reloaded->isEquipped());
        }
    }

    public function testFourthEquipReturns422WhenNoBonusSlots(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('slot-limit');
        $entries = [];
        for ($i = 0; $i < 4; ++$i) {
            $item = $this->createItem(sprintf('__test__eq-limit-%d-%s', $i, uniqid('', true)));
            $entries[] = $this->grantInventory($user, $item);
        }

        for ($i = 0; $i < 3; ++$i) {
            $this->client->jsonRequest(
                'POST',
                sprintf('/api/inventory/%d/equip', $entries[$i]->getId()),
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
            );
            self::assertResponseIsSuccessful();
        }

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $entries[3]->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('message', $payload);
    }

    public function testBonusEquipSlotsIncreasesLimit(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('bonus-slot');
        $bagItem = $this->createItemWithBonusSlots(
            sprintf('__test__eq-bag-%s', uniqid('', true)),
            bonusEquipSlots: 1
        );
        $bagEntry = $this->grantInventory($user, $bagItem);
        $extraEntries = [];
        for ($i = 0; $i < 3; ++$i) {
            $item = $this->createItem(sprintf('__test__eq-extra-%d-%s', $i, uniqid('', true)));
            $extraEntries[] = $this->grantInventory($user, $item);
        }

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/inventory/%d/equip', $bagEntry->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();

        foreach ($extraEntries as $entry) {
            $this->client->jsonRequest(
                'POST',
                sprintf('/api/inventory/%d/equip', $entry->getId()),
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
            );
            self::assertResponseIsSuccessful();
        }

        self::assertSame(4, InventoryService::BASE_EQUIP_SLOTS + 1);
    }

    private function createAuthenticatedUser(string $tag): array
    {
        $email = sprintf('__test__eq-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Eq12345!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Eq12345!');

        return ['user' => $user, 'token' => $token];
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

    private function createItemWithBonusSlots(string $name, int $bonusEquipSlots): Item
    {
        $item = (new Item())
            ->setName($name)
            ->setDescription('Item test slots bonus')
            ->setRarity(Rarity::COMMON)
            ->setBonusEquipSlots($bonusEquipSlots);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}

