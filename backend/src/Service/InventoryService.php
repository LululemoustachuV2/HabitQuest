<?php

namespace App\Service;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\User;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class InventoryService
{
    public const MAX_PAGE_SIZE = 50;

    public const BASE_EQUIP_SLOTS = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InventoryRepository $inventoryRepository,
    ) {
    }

    public function listForUser(User $user, int $limit = self::MAX_PAGE_SIZE, int $offset = 0): array
    {
        $limit = max(1, min(self::MAX_PAGE_SIZE, $limit));
        $offset = max(0, $offset);

        $entries = $this->inventoryRepository->findAllForUser($user, $limit, $offset);

        return array_map(
            fn (Inventory $entry): array => $this->toArray($entry),
            $entries
        );
    }

    public function grantItem(User $user, Item $item, bool $flush = true): Inventory
    {
        $entry = (new Inventory())
            ->setUser($user)
            ->setItem($item);

        $this->entityManager->persist($entry);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $entry;
    }

    public function getMaxEquipSlots(User $user): int
    {
        $bonus = 0;
        foreach ($this->inventoryRepository->findEquippedForUser($user) as $entry) {
            $bonus += $entry->getItem()->getBonusEquipSlots();
        }

        return self::BASE_EQUIP_SLOTS + $bonus;
    }

    public function toggleEquip(Inventory $entry): array
    {
        if ($entry->isEquipped()) {
            $entry->setIsEquipped(false);
            $this->entityManager->flush();

            return ['statusCode' => Response::HTTP_OK, 'entry' => $entry];
        }

        $user = $entry->getUser();
        $equippedCount = count($this->inventoryRepository->findEquippedForUser($user));
        if ($equippedCount >= $this->getMaxEquipSlots($user)) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => sprintf(
                    'Limite d\'équipement atteinte (%d/%d slots). Déséquipe un item ou équipe un objet qui accorde des slots supplémentaires.',
                    $equippedCount,
                    $this->getMaxEquipSlots($user)
                ),
            ];
        }

        $entry->setIsEquipped(true);
        $this->entityManager->flush();

        return ['statusCode' => Response::HTTP_OK, 'entry' => $entry];
    }

    public function toArray(Inventory $entry): array
    {
        $item = $entry->getItem();

        return [
            'id' => $entry->getId(),
            'acquiredAt' => $entry->getAcquiredAt()->format(\DateTimeInterface::ATOM),
            'isEquipped' => $entry->isEquipped(),
            'item' => [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'rarity' => $item->getRarity()->value,
                'bonusXpPercent' => $item->getBonusXpPercent(),
                'bonusGold' => $item->getBonusGold(),
                'bonusStat' => $item->getBonusStat()?->value,
                'bonusStatValue' => $item->getBonusStatValue(),
                'bonusEquipSlots' => $item->getBonusEquipSlots(),
            ],
        ];
    }
}

