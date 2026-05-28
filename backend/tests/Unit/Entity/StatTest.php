<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Stat;
use App\Entity\User;
use App\Enum\StatType;
use PHPUnit\Framework\TestCase;

final class StatTest extends TestCase
{
    public function testNewStatStartsWithAllZeroes(): void
    {
        $stat = new Stat();

        self::assertSame(0, $stat->getForce());
        self::assertSame(0, $stat->getIntelligence());
        self::assertSame(0, $stat->getDiscipline());
        self::assertSame(0, $stat->getCreativity());
    }

    public function testAddPointsIncrementsOnlyTheTargetStat(): void
    {
        $stat = (new Stat())->setUser(new User());

        $stat->addPoints(StatType::FORCE, 5);

        self::assertSame(5, $stat->getForce());
        self::assertSame(0, $stat->getIntelligence());
        self::assertSame(0, $stat->getDiscipline());
        self::assertSame(0, $stat->getCreativity());
    }

    public function testAddPointsIsCumulative(): void
    {
        $stat = (new Stat())->setUser(new User());

        $stat->addPoints(StatType::INTELLIGENCE, 3);
        $stat->addPoints(StatType::INTELLIGENCE, 7);

        self::assertSame(10, $stat->getIntelligence());
    }

    public function testAddPointsWithZeroIsNoop(): void
    {
        $stat = (new Stat())->setUser(new User());
        $stat->addPoints(StatType::DISCIPLINE, 12);

        $stat->addPoints(StatType::DISCIPLINE, 0);

        self::assertSame(12, $stat->getDiscipline());
    }

    public function testAddPointsRejectsNegativeValue(): void
    {
        $stat = (new Stat())->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);

        $stat->addPoints(StatType::CREATIVITY, -1);
    }

    public function testAddPointsDoesNotMutateOnNegativeValue(): void
    {
        $stat = (new Stat())->setUser(new User());
        $stat->addPoints(StatType::CREATIVITY, 4);

        try {
            $stat->addPoints(StatType::CREATIVITY, -10);
            self::fail('Une exception devait être levée pour un montant négatif.');
        } catch (\InvalidArgumentException) {
            self::assertSame(4, $stat->getCreativity());
        }
    }

    public function testGetReturnsValueFromEnum(): void
    {
        $stat = (new Stat())->setUser(new User());
        $stat->addPoints(StatType::FORCE, 11);
        $stat->addPoints(StatType::CREATIVITY, 22);

        self::assertSame(11, $stat->get(StatType::FORCE));
        self::assertSame(0, $stat->get(StatType::INTELLIGENCE));
        self::assertSame(0, $stat->get(StatType::DISCIPLINE));
        self::assertSame(22, $stat->get(StatType::CREATIVITY));
    }

    public function testToArrayExposesAllFourStats(): void
    {
        $stat = (new Stat())->setUser(new User());
        $stat->addPoints(StatType::FORCE, 1);
        $stat->addPoints(StatType::INTELLIGENCE, 2);
        $stat->addPoints(StatType::DISCIPLINE, 3);
        $stat->addPoints(StatType::CREATIVITY, 4);

        self::assertSame(
            [
                'force' => 1,
                'intelligence' => 2,
                'discipline' => 3,
                'creativity' => 4,
            ],
            $stat->toArray()
        );
    }
}

