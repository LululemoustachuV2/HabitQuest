<?php

namespace App\Tests\Unit\Service;

use App\Entity\Item;
use App\Enum\Rarity;
use App\Repository\InventoryRepository;
use App\Repository\ItemRepository;
use App\Repository\ShopPurchaseRepository;
use App\Service\InventoryService;
use App\Service\ItemService;
use App\Service\ShopService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShopServiceTest extends TestCase
{
    public function testRotationIsDeterministicForSameDate(): void
    {
        $items = $this->buildSellableItems(8);
        $service = $this->createShopService($items);

        $first = $service->buildRotation('2026-05-28');
        $second = $service->buildRotation('2026-05-28');

        self::assertSame(
            array_map(static fn (Item $i): ?int => $i->getId(), $first),
            array_map(static fn (Item $i): ?int => $i->getId(), $second),
        );
        self::assertGreaterThanOrEqual(4, count($first));
        self::assertLessThanOrEqual(6, count($first));
    }

    public function testRotationChangesWhenDateChanges(): void
    {
        $items = $this->buildSellableItems(8);
        $service = $this->createShopService($items);

        $dayA = $service->buildRotation('2026-05-28');
        $dayB = $service->buildRotation('2026-05-29');

        $idsA = array_map(static fn (Item $i): ?int => $i->getId(), $dayA);
        $idsB = array_map(static fn (Item $i): ?int => $i->getId(), $dayB);

        self::assertNotSame($idsA, $idsB);
    }

    private function createShopService(array $items): ShopService
    {
        $sellableRepo = $this->createMock(ItemRepository::class);
        $sellableRepo->method('findSellableWithPrice')->willReturn($items);

        $em = $this->createMock(EntityManagerInterface::class);
        $catalogRepo = $this->createMock(ItemRepository::class);
        $itemService = new ItemService($em, $catalogRepo);

        $inventoryRepo = $this->createMock(InventoryRepository::class);
        $inventoryService = new InventoryService($em, $inventoryRepo);

        $shopPurchaseRepo = $this->createMock(ShopPurchaseRepository::class);
        $shopPurchaseRepo->method('findPurchasedItemIdsForUserAndDate')->willReturn([]);

        return new ShopService(
            $sellableRepo,
            $shopPurchaseRepo,
            $itemService,
            $inventoryService,
            $em,
        );
    }

    private function buildSellableItems(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; ++$i) {
            $item = (new Item())
                ->setName('Item '.$i)
                ->setDescription('Test')
                ->setRarity(Rarity::COMMON)
                ->setIsSellable(true)
                ->setShopPrice(10 + $i);
            $reflection = new \ReflectionProperty(Item::class, 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($item, $i);
            $items[] = $item;
        }

        return $items;
    }
}

