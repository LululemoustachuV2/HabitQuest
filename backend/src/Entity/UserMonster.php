<?php

namespace App\Entity;

use App\Repository\UserMonsterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserMonsterRepository::class)]
#[ORM\Table(name: 'user_monsters')]
#[ORM\Index(name: 'idx_user_monsters_user', columns: ['user_id'])]
class UserMonster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: MonsterTemplate::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private MonsterTemplate $template;

    #[ORM\Column(type: 'integer')]
    private int $currentHp = 0;

    #[ORM\Column(type: 'integer')]
    private int $maxHp = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getTemplate(): MonsterTemplate
    {
        return $this->template;
    }

    public function setTemplate(MonsterTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getCurrentHp(): int
    {
        return $this->currentHp;
    }

    public function setCurrentHp(int $currentHp): self
    {
        $this->currentHp = max(0, $currentHp);

        return $this;
    }

    public function getMaxHp(): int
    {
        return $this->maxHp;
    }

    public function setMaxHp(int $maxHp): self
    {
        $this->maxHp = max(1, $maxHp);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

