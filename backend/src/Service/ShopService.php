<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\ShopPurchase;
use App\Entity\User;
use App\Repository\ItemRepository;
use App\Repository\ShopPurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ShopService
{
    public const TIMEZONE = 'Europe/Paris';

    private const MIN_ROTATION_SIZE = 4;

    private const MAX_ROTATION_SIZE = 6;

    public function __construct(
        private readonly ItemRepository $itemRepository,
        private readonly ShopPurchaseRepository $shopPurchaseRepository,
        private readonly ItemService $itemService,
        private readonly InventoryService $inventoryService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getShop(User $user): array
    {
        $rotationDate = $this->todayParis();
        $rotationItems = $this->buildRotation($rotationDate);
        $purchasedIds = $this->shopPurchaseRepository->findPurchasedItemIdsForUserAndDate($user, $rotationDate);

        $itemsById = [];
        foreach ($rotationItems as $item) {
            $itemsById[(int) $item->getId()] = $item;
        }

        foreach ($purchasedIds as $itemId) {
            if (isset($itemsById[$itemId])) {
                continue;
            }
            $purchasedItem = $this->itemRepository->find($itemId);
            if ($purchasedItem instanceof Item && $purchasedItem->isSellable() && $purchasedItem->getShopPrice() !== null) {
                $itemsById[$itemId] = $purchasedItem;
            }
        }

        $merged = array_values($itemsById);
        usort(
            $merged,
            static function (Item $a, Item $b) use ($rotationDate): int {
                $ha = hash('crc32b', $rotationDate.'|'.$a->getId());
                $hb = hash('crc32b', $rotationDate.'|'.$b->getId());

                return strcmp($ha, $hb);
            },
        );

        $purchasedSet = array_fill_keys($purchasedIds, true);

        return [
            'statusCode' => Response::HTTP_OK,
            'rotationDate' => $rotationDate,
            'items' => array_map(
                function (Item $item) use ($purchasedSet): array {
                    $row = $this->toShopItemArray($item);
                    $row['purchased'] = isset($purchasedSet[(int) $item->getId()]);

                    return $row;
                },
                $merged,
            ),
        ];
    }

    public function purchase(User $user, int $itemId): array
    {
        $item = $this->itemRepository->find($itemId);
        if (!$item instanceof Item || !$item->isSellable() || $item->getShopPrice() === null) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Cet item n\'est pas disponible à la boutique.',
            ];
        }

        $rotationDate = $this->todayParis();
        $rotationIds = array_map(
            static fn (Item $i): int => (int) $i->getId(),
            $this->buildRotation($rotationDate),
        );

        if (!in_array($itemId, $rotationIds, true)) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Cet item n\'est pas dans la rotation du jour.',
            ];
        }

        if ($this->shopPurchaseRepository->hasPurchased($user, $item, $rotationDate)) {
            return [
                'statusCode' => Response::HTTP_OK,
                'message' => 'Article déjà acheté aujourd\'hui.',
                'gold' => $user->getGold(),
                'purchased' => true,
            ];
        }

        $price = $item->getShopPrice();
        if ($user->getGold() < $price) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Or insuffisant pour cet achat.',
            ];
        }

        $user->spendGold($price);
        $entry = $this->inventoryService->grantItem($user, $item, flush: false);

        $purchase = (new ShopPurchase())
            ->setUser($user)
            ->setItem($item)
            ->setRotationDate($rotationDate);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Achat effectué.',
            'gold' => $user->getGold(),
            'inventoryEntry' => $this->inventoryService->toArray($entry),
            'purchased' => true,
        ];
    }

    public function buildRotation(?string $rotationDate = null): array
    {
        $rotationDate ??= $this->todayParis();
        $sellable = $this->itemRepository->findSellableWithPrice();
        if ($sellable === []) {
            return [];
        }

        usort(
            $sellable,
            static function (Item $a, Item $b) use ($rotationDate): int {
                $ha = hash('crc32b', $rotationDate.'|'.$a->getId());
                $hb = hash('crc32b', $rotationDate.'|'.$b->getId());

                return strcmp($ha, $hb);
            },
        );

        $targetCount = $this->rotationSizeForDate($rotationDate, count($sellable));

        return array_slice($sellable, 0, $targetCount);
    }

    private function rotationSizeForDate(string $rotationDate, int $availableCount): int
    {
        $desired = self::MIN_ROTATION_SIZE + (crc32($rotationDate) % (self::MAX_ROTATION_SIZE - self::MIN_ROTATION_SIZE + 1));

        return min($availableCount, max(1, min(self::MAX_ROTATION_SIZE, $desired)));
    }

    private function todayParis(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE)))->format('Y-m-d');
    }

    private function toShopItemArray(Item $item): array
    {
        return [
            ...$this->itemService->toArray($item),
            'shopPrice' => $item->getShopPrice(),
        ];
    }
}

