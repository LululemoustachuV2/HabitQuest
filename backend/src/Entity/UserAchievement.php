<?php

namespace App\Entity;

use App\Repository\UserAchievementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAchievementRepository::class)]
#[ORM\Table(name: 'user_achievements')]
#[ORM\UniqueConstraint(name: 'uniq_user_achievements_user_achievement', columns: ['user_id', 'achievement_id'])]
#[ORM\Index(name: 'idx_user_achievements_user', columns: ['user_id'])]
class UserAchievement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Achievement::class)]
    #[ORM\JoinColumn(name: 'achievement_id', nullable: false, onDelete: 'CASCADE')]
    private Achievement $achievement;

    #[ORM\Column(name: 'unlocked_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    public function __construct(User $user, Achievement $achievement, ?\DateTimeImmutable $unlockedAt = null)
    {
        $this->user = $user;
        $this->achievement = $achievement;
        $this->unlockedAt = $unlockedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAchievement(): Achievement
    {
        return $this->achievement;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }
}

