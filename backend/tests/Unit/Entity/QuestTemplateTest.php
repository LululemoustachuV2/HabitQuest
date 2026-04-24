<?php

namespace App\Tests\Unit\Entity;

use App\Entity\QuestTemplate;
use PHPUnit\Framework\TestCase;

final class QuestTemplateTest extends TestCase
{
    public function testXpRewardCannotBeNegative(): void
    {
        $template = (new QuestTemplate())->setXpReward(-20);

        self::assertSame(0, $template->getXpReward());
    }

    public function testRequiredLevelCannotBeLowerThanOne(): void
    {
        $template = (new QuestTemplate())->setRequiredLevel(0);

        self::assertSame(1, $template->getRequiredLevel());
    }
}
