<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ORM\Table(name: 'inventories')]
#[ORM\Index(name: 'idx_inventories_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_inventories_item', columns: ['item_id'])]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'item_id', nullable: false, onDelete: 'RESTRICT')]
    private Item $item;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $acquiredAt;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isEquipped = false;

    public function __construct()
    {
        $this->acquiredAt = new \DateTimeImmutable();
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

    public function getAcquiredAt(): \DateTimeImmutable
    {
        return $this->acquiredAt;
    }

    public function setAcquiredAt(\DateTimeImmutable $acquiredAt): self
    {
        $this->acquiredAt = $acquiredAt;

        return $this;
    }

    public function isEquipped(): bool
    {
        return $this->isEquipped;
    }

    public function setIsEquipped(bool $isEquipped): self
    {
        $this->isEquipped = $isEquipped;

        return $this;
    }
}

