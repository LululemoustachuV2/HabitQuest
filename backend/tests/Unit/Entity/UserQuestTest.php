<?php

namespace App\Tests\Unit\Entity;

use App\Entity\UserQuest;
use App\Enum\UserQuestStatus;
use PHPUnit\Framework\TestCase;

final class UserQuestTest extends TestCase
{
    public function testMarkCompletedUpdatesStatusAndFlags(): void
    {
        $quest = new UserQuest();

        self::assertFalse($quest->isValidated());
        self::assertNull($quest->getCompletedAt());

        $quest->markCompleted();

        self::assertTrue($quest->isValidated());
        self::assertSame(UserQuestStatus::COMPLETED, $quest->getStatus());
        self::assertNotNull($quest->getCompletedAt());
    }
}
