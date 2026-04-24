<?php

namespace App\Entity;

use App\Repository\UserQuestActionLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserQuestActionLogRepository::class)]
#[ORM\Table(name: 'user_quest_action_logs')]
class UserQuestActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: UserQuest::class)]
    #[ORM\JoinColumn(name: 'user_quest_id', nullable: false, onDelete: 'CASCADE')]
    private UserQuest $userQuest;

    #[ORM\Column(type: 'text')]
    private string $comment = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $actionAt;

    public function __construct()
    {
        $this->actionAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setUserQuest(UserQuest $userQuest): self
    {
        $this->userQuest = $userQuest;

        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
