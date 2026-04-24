<?php

namespace App\Entity;

use App\Enum\UserQuestStatus;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserQuestRepository::class)]
#[ORM\Table(name: 'user_quests')]
#[ORM\Index(name: 'idx_user_quests_user_template', columns: ['user_id', 'quest_template_id'])]
class UserQuest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: QuestTemplate::class)]
    #[ORM\JoinColumn(name: 'quest_template_id', nullable: false)]
    private QuestTemplate $questTemplate;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'event_id', nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(type: 'string', length: 20, enumType: UserQuestStatus::class)]
    private UserQuestStatus $status = UserQuestStatus::IN_PROGRESS;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidated = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
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

    public function getQuestTemplate(): QuestTemplate
    {
        return $this->questTemplate;
    }

    public function setQuestTemplate(QuestTemplate $questTemplate): self
    {
        $this->questTemplate = $questTemplate;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getStatus(): UserQuestStatus
    {
        return $this->status;
    }

    public function setStatus(UserQuestStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function markCompleted(): self
    {
        $this->status = UserQuestStatus::COMPLETED;
        $this->isValidated = true;
        $this->completedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markExpired(): self
    {
        $this->status = UserQuestStatus::EXPIRED;

        return $this;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
