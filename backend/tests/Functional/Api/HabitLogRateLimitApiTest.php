<?php

namespace App\Tests\Functional\Api;

use App\Entity\Habit;
use App\Entity\User;
use App\Enum\UserRole;

final class HabitLogRateLimitApiTest extends ApiTestCase
{
    public function testTooManyHabitLogsReturn429(): void
    {
        $email = 'habit-log-rate-'.uniqid('', true).'@test.dev';
        $user = $this->createUser($email, 'Password123!', UserRole::USER);
        $habit = $this->createHabit($user);
        $token = $this->authenticate($this->client, $email, 'Password123!');

        for ($i = 0; $i < 3; ++$i) {
            $this->client->jsonRequest(
                'POST',
                sprintf('/api/habits/%d/log', $habit->getId()),
                [],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
            );
            self::assertResponseStatusCodeSame(201);
        }

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)],
        );

        self::assertResponseStatusCodeSame(429);
    }

    private function createHabit(User $user): Habit
    {
        $habit = (new Habit())
            ->setUser($user)
            ->setName('Rate limit habit')
            ->setXpReward(5)
            ->setGoldReward(1)
            ->setIsActive(true);

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return $habit;
    }
}

