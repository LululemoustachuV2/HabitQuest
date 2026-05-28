<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email fourni n'est pas valide.")]
    #[Assert\Length(max: 180)]
    private string $email = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $password = '';

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $xp = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $gold = 0;

    #[ORM\Column(type: 'string', length: 10, enumType: UserRole::class)]
    private UserRole $role = UserRole::USER;

    #[ORM\Column(name: 'current_streak', type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $currentStreak = 0;

    #[ORM\Column(name: 'longest_streak', type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $longestStreak = 0;

    #[ORM\Column(name: 'last_streak_date', type: 'string', length: 10, nullable: true)]
    private ?string $lastStreakDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === UserRole::ADMIN) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function addXp(int $amount): self
    {
        $this->xp += max(0, $amount);

        return $this;
    }

    public function setXp(int $xp): self
    {
        if ($xp < 0) {
            throw new \InvalidArgumentException(sprintf(
                'L\'XP doit être positif ou nul, %d reçu.',
                $xp
            ));
        }

        $this->xp = $xp;

        return $this;
    }

    public function getGold(): int
    {
        return $this->gold;
    }

    public function addGold(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Le montant de gold à ajouter doit être positif ou nul, %d reçu.',
                $amount
            ));
        }

        $this->gold += $amount;

        return $this;
    }

    public function setGold(int $gold): self
    {
        if ($gold < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Le gold doit être positif ou nul, %d reçu.',
                $gold
            ));
        }

        $this->gold = $gold;

        return $this;
    }

    public function spendGold(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException(sprintf(
                'Le montant à dépenser doit être positif ou nul, %d reçu.',
                $amount
            ));
        }

        if ($this->gold < $amount) {
            throw new \InvalidArgumentException('Or insuffisant.');
        }

        $this->gold -= $amount;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getCurrentStreak(): int
    {
        return $this->currentStreak;
    }

    public function setCurrentStreak(int $currentStreak): self
    {
        $this->currentStreak = max(0, $currentStreak);

        return $this;
    }

    public function getLongestStreak(): int
    {
        return $this->longestStreak;
    }

    public function setLongestStreak(int $longestStreak): self
    {
        $this->longestStreak = max(0, $longestStreak);

        return $this;
    }

    public function getLastStreakDate(): ?string
    {
        return $this->lastStreakDate;
    }

    public function setLastStreakDate(?string $lastStreakDate): self
    {
        $this->lastStreakDate = $lastStreakDate;

        return $this;
    }
}

