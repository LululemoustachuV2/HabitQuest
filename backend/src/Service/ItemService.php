<?php

namespace App\Service;

use App\Dto\ItemDto;
use App\Entity\Item;
use App\Enum\BonusStat;
use App\Enum\Rarity;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ItemService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ItemRepository $itemRepository,
    ) {
    }

    public function createItem(ItemDto $dto): array
    {
        $item = new Item();
        $this->hydrateFromDto($item, $dto);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Item créé.',
            'item' => $this->toArray($item),
        ];
    }

    public function updateItem(int $id, ItemDto $dto): array
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof Item) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Item introuvable.',
            ];
        }

        $this->hydrateFromDto($item, $dto);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Item mis à jour.',
            'item' => $this->toArray($item),
        ];
    }

    public function deleteItem(int $id): array
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof Item) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Item introuvable.',
            ];
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Item supprimé.',
        ];
    }

    public function getItem(int $id): array
    {
        $item = $this->itemRepository->find($id);
        if (!$item instanceof Item) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Item introuvable.',
            ];
        }

        return [
            'statusCode' => Response::HTTP_OK,
            'item' => $this->toArray($item),
        ];
    }

    public function toArray(Item $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'rarity' => $item->getRarity()->value,
            'bonusXpPercent' => $item->getBonusXpPercent(),
            'bonusGold' => $item->getBonusGold(),
            'bonusStat' => $item->getBonusStat()?->value,
            'bonusStatValue' => $item->getBonusStatValue(),
            'bonusEquipSlots' => $item->getBonusEquipSlots(),
            'isSellable' => $item->isSellable(),
            'shopPrice' => $item->getShopPrice(),
        ];
    }

    private function hydrateFromDto(Item $item, ItemDto $dto): void
    {
        $isSellable = (bool) ($dto->isSellable ?? false);

        $item
            ->setName((string) $dto->name)
            ->setDescription((string) $dto->description)
            ->setRarity(Rarity::from((string) $dto->rarity))
            ->setBonusXpPercent((int) $dto->bonusXpPercent)
            ->setBonusGold((int) $dto->bonusGold)
            ->setBonusStat($dto->bonusStat !== null ? BonusStat::from($dto->bonusStat) : null)
            ->setBonusStatValue((int) $dto->bonusStatValue)
            ->setBonusEquipSlots((int) ($dto->bonusEquipSlots ?? 0))
            ->setIsSellable($isSellable);

        if ($isSellable) {
            $item->setShopPrice((int) $dto->shopPrice);
        }
    }
}

