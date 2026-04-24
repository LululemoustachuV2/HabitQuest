<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testAdminHasAdminAndUserRoles(): void
    {
        $user = (new User())
            ->setEmail('admin@test.dev')
            ->setRole(UserRole::ADMIN);

        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testRegularUserHasOnlyUserRole(): void
    {
        $user = (new User())
            ->setEmail('user@test.dev')
            ->setRole(UserRole::USER);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
