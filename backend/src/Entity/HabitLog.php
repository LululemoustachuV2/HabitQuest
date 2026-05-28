<?php

namespace App\Entity;

use App\Repository\HabitLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HabitLogRepository::class)]
#[ORM\Table(name: 'habit_logs')]
#[ORM\Index(name: 'idx_habit_logs_user_logged_at', columns: ['user_id', 'logged_at'])]
#[ORM\Index(name: 'idx_habit_logs_habit', columns: ['habit_id'])]
class HabitLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Habit::class)]
    #[ORM\JoinColumn(name: 'habit_id', nullable: false, onDelete: 'CASCADE')]
    private Habit $habit;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'logged_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $loggedAt;

    #[ORM\Column(name: 'xp_earned', type: 'integer', options: ['default' => 0])]
    private int $xpEarned;

    #[ORM\Column(name: 'gold_earned', type: 'integer', options: ['default' => 0])]
    private int $goldEarned;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    public function __construct(
        Habit $habit,
        User $user,
        int $xpEarned,
        int $goldEarned,
        ?string $note = null,
        ?\DateTimeImmutable $loggedAt = null,
    ) {
        if ($xpEarned < 0) {
            throw new \InvalidArgumentException(sprintf(
                'xpEarned doit être positif ou nul (reçu : %d).',
                $xpEarned
            ));
        }
        if ($goldEarned < 0) {
            throw new \InvalidArgumentException(sprintf(
                'goldEarned doit être positif ou nul (reçu : %d).',
                $goldEarned
            ));
        }

        $this->habit = $habit;
        $this->user = $user;
        $this->xpEarned = $xpEarned;
        $this->goldEarned = $goldEarned;
        $this->note = $note;
        $this->loggedAt = $loggedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHabit(): Habit
    {
        return $this->habit;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLoggedAt(): \DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function getXpEarned(): int
    {
        return $this->xpEarned;
    }

    public function getGoldEarned(): int
    {
        return $this->goldEarned;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}

