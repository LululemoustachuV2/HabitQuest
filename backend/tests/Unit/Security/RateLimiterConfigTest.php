<?php

namespace App\Tests\Unit\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimiterConfigTest extends KernelTestCase
{
    public function testHabitLogLimiterServiceIsRegistered(): void
    {
        self::bootKernel();

        $habitLog = self::getContainer()->get('limiter.habit_log');
        self::assertInstanceOf(RateLimiterFactory::class, $habitLog);
        self::assertTrue($habitLog->create('user-1')->consume()->isAccepted());
    }

    public function testHabitLogLimiterBlocksAfterConfiguredBurst(): void
    {
        self::bootKernel();

        $habitLog = self::getContainer()->get('limiter.habit_log');
        $limiter = $habitLog->create('burst-user-'.uniqid('', true));

        self::assertTrue($limiter->consume()->isAccepted());
        self::assertTrue($limiter->consume()->isAccepted());
        self::assertTrue($limiter->consume()->isAccepted());
        self::assertFalse($limiter->consume()->isAccepted());
    }
}

