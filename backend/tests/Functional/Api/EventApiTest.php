<?php

namespace App\Tests\Functional\Api;

use App\Entity\Event;
use App\Entity\Habit;
use App\Entity\User;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\EventRepository;

final class EventApiTest extends ApiTestCase
{
    public function testAdminCannotCreateGlobalEventWithNonEventTemplate(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $dailyTemplate = $this->createQuestTemplate(
            sprintf('__test__Daily template %s', uniqid('', true)),
            QuestKind::DAILY,
            30
        );

        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 120,
                'questTemplateIds' => [$dailyTemplate->getId()],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminCanCreateEventWithMultipliersAndBonusRules(): void
    {
        $adminEmail = sprintf('__test__admin-create-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $eventTemplate = $this->createQuestTemplate(
            sprintf('__test__Event template %s', uniqid('', true)),
            QuestKind::EVENT,
            0
        );

        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('-1 hour'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 50,
                'questTemplateIds' => [$eventTemplate->getId()],
                'xpMultiplier' => 2.0,
                'goldMultiplier' => 1.5,
                'bonusRules' => ['theme' => 'weekend_x2'],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertEqualsWithDelta(2.0, (float) ($payload['event']['xpMultiplier'] ?? 0), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1.5, (float) ($payload['event']['goldMultiplier'] ?? 0), PHP_FLOAT_EPSILON);
        self::assertSame(['theme' => 'weekend_x2'], $payload['event']['bonusRules'] ?? null);
    }

    public function testActiveEventMultipliesHabitLogRewards(): void
    {
        $adminEmail = sprintf('__test__admin-hl-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $userEmail = sprintf('__test__event-user-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($userEmail, 'User1234!', UserRole::USER);
        $habit = $this->createHabit($user, 'Sport', xpReward: 20, goldReward: 10);

        $eventTemplate = $this->createQuestTemplate(
            sprintf('__test__Event hl %s', uniqid('', true)),
            QuestKind::EVENT,
            0
        );

        $adminToken = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('-1 hour'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 0,
                'questTemplateIds' => [$eventTemplate->getId()],
                'xpMultiplier' => 2.0,
                'goldMultiplier' => 2.0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );
        self::assertResponseStatusCodeSame(201);
        $this->entityManager->clear();

        $eventRepo = static::getContainer()->get(EventRepository::class);
        self::assertCount(1, $eventRepo->findActiveAt(new \DateTimeImmutable()));

        $this->resetStreak($user);

        $userToken = $this->authenticate($this->client, $userEmail, 'User1234!');
        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $userToken)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(42, $payload['xpEarned'] ?? null);
        self::assertSame(20, $payload['goldEarned'] ?? null);
    }

    public function testEventWithoutMultipliersPreservesMvpHabitLogAmounts(): void
    {
        $adminEmail = sprintf('__test__admin-mvp-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $userEmail = sprintf('__test__event-mvp-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($userEmail, 'User1234!', UserRole::USER);
        $habit = $this->createHabit($user, 'Lecture', xpReward: 25, goldReward: 10);

        $futureTemplate = $this->createQuestTemplate(
            sprintf('__test__Future event %s', uniqid('', true)),
            QuestKind::EVENT,
            0
        );

        $adminToken = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('+2 days'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+3 days'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 100,
                'questTemplateIds' => [$futureTemplate->getId()],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );
        self::assertResponseStatusCodeSame(201);

        $this->resetStreak($user);

        $userToken = $this->authenticate($this->client, $userEmail, 'User1234!');
        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $userToken)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(26, $payload['xpEarned'] ?? null);
        self::assertSame(10, $payload['goldEarned'] ?? null);
    }

    public function testAdminCanUpdateEventMultipliers(): void
    {
        $adminEmail = sprintf('__test__admin-upd-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $eventTemplate = $this->createQuestTemplate(
            sprintf('__test__Event upd %s', uniqid('', true)),
            QuestKind::EVENT,
            0
        );

        $adminToken = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+2 days'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 10,
                'questTemplateIds' => [$eventTemplate->getId()],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );
        self::assertResponseStatusCodeSame(201);
        $created = json_decode((string) $this->client->getResponse()->getContent(), true);
        $eventId = (int) ($created['event']['id'] ?? 0);
        self::assertGreaterThan(0, $eventId);

        $this->client->jsonRequest(
            'PUT',
            sprintf('/api/admin/events/%d', $eventId),
            [
                'startsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+2 days'))->format(\DateTimeInterface::ATOM),
                'xpMultiplier' => 3.0,
                'goldMultiplier' => 2.5,
                'bonusRules' => ['label' => 'updated'],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );

        self::assertResponseIsSuccessful();
        $updated = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertEqualsWithDelta(3.0, (float) ($updated['event']['xpMultiplier'] ?? 0), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2.5, (float) ($updated['event']['goldMultiplier'] ?? 0), PHP_FLOAT_EPSILON);
        self::assertSame(['label' => 'updated'], $updated['event']['bonusRules'] ?? null);

        $this->entityManager->clear();
        $eventRepo = static::getContainer()->get(EventRepository::class);
        $event = $eventRepo->find($eventId);
        self::assertInstanceOf(Event::class, $event);
        self::assertEqualsWithDelta(3.0, $event->getXpMultiplier(), PHP_FLOAT_EPSILON);
    }

    private function resetStreak(User $user): void
    {
        $user->setCurrentStreak(0);
        $user->setLastStreakDate(null);
        $this->entityManager->flush();
    }

    public function testQuestValidationAppliesActiveEventMultiplier(): void
    {
        $adminEmail = sprintf('__test__admin-qv-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);

        $userEmail = sprintf('__test__quest-event-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($userEmail, 'User1234!', UserRole::USER);
        $dailyTemplate = $this->createQuestTemplate(
            sprintf('__test__Daily event mult %s', uniqid('', true)),
            QuestKind::DAILY,
            40
        );
        $userQuest = $this->createUserQuest($user, $dailyTemplate);

        $eventTemplate = $this->createQuestTemplate(
            sprintf('__test__Event qv %s', uniqid('', true)),
            QuestKind::EVENT,
            0
        );

        $adminToken = $this->authenticate($this->client, $adminEmail, 'Admin1234!');
        $this->client->jsonRequest(
            'POST',
            '/api/admin/events',
            [
                'startsAt' => (new \DateTimeImmutable('-1 hour'))->format(\DateTimeInterface::ATOM),
                'endsAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
                'eventXpReward' => 0,
                'questTemplateIds' => [$eventTemplate->getId()],
                'xpMultiplier' => 2.0,
                'goldMultiplier' => 1.0,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );
        self::assertResponseStatusCodeSame(201);

        $userToken = $this->authenticate($this->client, $userEmail, 'User1234!');
        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Validation sous event x2'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $userToken)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(80, $payload['xpAwarded'] ?? null);

        $this->entityManager->clear();
        $refreshedUser = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $refreshedUser);
        self::assertSame(80, $refreshedUser->getXp());
    }

    private function createHabit(
        User $user,
        string $name,
        int $xpReward = 10,
        int $goldReward = 5,
    ): Habit {
        $habit = (new Habit())
            ->setUser($user)
            ->setName($name)
            ->setDescription('Habitude de test event.')
            ->setXpReward($xpReward)
            ->setGoldReward($goldReward)
            ->setIsActive(true);

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return $habit;
    }
}

