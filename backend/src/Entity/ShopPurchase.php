<?php

namespace App\Entity;

use App\Repository\ShopPurchaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopPurchaseRepository::class)]
#[ORM\Table(name: 'shop_purchases')]
#[ORM\UniqueConstraint(name: 'uniq_shop_purchase_user_item_date', columns: ['user_id', 'item_id', 'rotation_date'])]
class ShopPurchase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\Column(name: 'rotation_date', type: 'string', length: 10)]
    private string $rotationDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $purchasedAt;

    public function __construct()
    {
        $this->purchasedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getRotationDate(): string
    {
        return $this->rotationDate;
    }

    public function setRotationDate(string $rotationDate): self
    {
        $this->rotationDate = $rotationDate;

        return $this;
    }

    public function getPurchasedAt(): \DateTimeImmutable
    {
        return $this->purchasedAt;
    }
}

