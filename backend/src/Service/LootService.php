<?php

namespace App\Service;

use App\Entity\MonsterTemplate;
use App\Entity\User;
use App\Repository\ItemRepository;
use App\Service\Random\RandomPickerInterface;

final class LootService
{
    public function __construct(
        private readonly ItemRepository $itemRepository,
        private readonly InventoryService $inventoryService,
        private readonly RandomPickerInterface $randomPicker,
    ) {
    }

    public function generateLoot(User $user, MonsterTemplate $template, bool $flush = false): ?array
    {
        $entry = $this->pickLootEntry($template->getLootTable());
        if ($entry === null) {
            return null;
        }

        $item = $this->itemRepository->find($entry['itemId']);
        if ($item === null) {
            return null;
        }

        $inventory = $this->inventoryService->grantItem($user, $item, $flush);

        return [
            'inventoryId' => $inventory->getId(),
            'item' => [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'rarity' => $item->getRarity()->value,
            ],
        ];
    }

    public function pickLootEntry(array $lootTable): ?array
    {
        if ($lootTable === []) {
            return null;
        }

        $totalWeight = 0;
        foreach ($lootTable as $entry) {
            $totalWeight += max(0, (int) ($entry['weight'] ?? 0));
        }

        if ($totalWeight <= 0) {
            return null;
        }

        $roll = $this->randomPicker->pickInt(1, $totalWeight);
        $cursor = 0;
        foreach ($lootTable as $entry) {
            $weight = max(0, (int) ($entry['weight'] ?? 0));
            if ($weight <= 0) {
                continue;
            }
            $cursor += $weight;
            if ($roll <= $cursor) {
                return [
                    'itemId' => (int) $entry['itemId'],
                    'weight' => $weight,
                ];
            }
        }

        return $lootTable[array_key_last($lootTable)] ?? null;
    }
}

