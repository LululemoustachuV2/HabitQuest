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

    public function testNewUserStartsWithZeroGold(): void
    {
        $user = new User();

        self::assertSame(0, $user->getGold());
    }

    public function testAddGoldIsAdditive(): void
    {
        $user = new User();

        $user->addGold(10)->addGold(25);

        self::assertSame(35, $user->getGold());
    }

    public function testAddGoldWithZeroIsIdempotent(): void
    {
        $user = new User();
        $user->addGold(42);

        $user->addGold(0);
        $user->addGold(0);

        self::assertSame(42, $user->getGold());
    }

    public function testAddGoldReturnsFluentInterface(): void
    {
        $user = new User();

        self::assertSame($user, $user->addGold(5));
    }

    public function testAddGoldRejectsNegativeAmount(): void
    {
        $user = new User();
        $user->addGold(50);

        $this->expectException(\InvalidArgumentException::class);

        $user->addGold(-1);
    }

    public function testAddGoldDoesNotMutateOnNegativeAmount(): void
    {
        $user = new User();
        $user->addGold(50);

        try {
            $user->addGold(-100);
            self::fail('Une exception devait être levée pour un montant négatif.');
        } catch (\InvalidArgumentException) {
            self::assertSame(50, $user->getGold());
        }
    }
}

