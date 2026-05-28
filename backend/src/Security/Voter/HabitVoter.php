<?php

namespace App\Security\Voter;

use App\Entity\Habit;
use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class HabitVoter extends Voter
{
    public const VIEW = 'HABIT_VIEW';
    public const EDIT = 'HABIT_EDIT';
    public const DELETE = 'HABIT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof Habit;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Habit) {
            return false;
        }

        if ($user->getRole() === UserRole::ADMIN) {
            return true;
        }

        return $subject->getUser()->getId() === $user->getId();
    }
}

