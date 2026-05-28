<?php

namespace App\Security\Voter;

use App\Entity\Inventory;
use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class InventoryVoter extends Voter
{
    public const EQUIP = 'INVENTORY_EQUIP';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::EQUIP) {
            return false;
        }

        return $subject instanceof Inventory;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Inventory) {
            return false;
        }

        if ($user->getRole() === UserRole::ADMIN) {
            return true;
        }

        return $subject->getUser()->getId() === $user->getId();
    }
}

