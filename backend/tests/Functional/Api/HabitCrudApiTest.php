<?php

namespace App\Tests\Functional\Api;

use App\Entity\Category;
use App\Entity\Habit;
use App\Entity\User;
use App\Enum\StatType;
use App\Enum\UserRole;
use App\Repository\HabitRepository;

final class HabitCrudApiTest extends ApiTestCase
{
    public function testAnonymousCannotListHabits(): void
    {
        $this->client->jsonRequest('GET', '/api/habits');

        self::assertResponseStatusCodeSame(401);
    }

    public function testUserCannotCreateHabitInV2(): void
    {
        ['token' => $token] = $this->createAuthenticatedUser();

        $this->client->jsonRequest(
            'POST',
            '/api/habits',
            [
                'name' => 'Courir 20 min',
                'description' => 'Footing',
                'xpReward' => 25,
                'goldReward' => 10,
                'isActive' => true,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testUserCanListOwnHabits(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();
        $this->createHabit($user, 'Habitude admin seed');

        $this->client->jsonRequest(
            'GET',
            '/api/habits',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $list = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($list);
        self::assertArrayHasKey('items', $list);
        self::assertGreaterThanOrEqual(1, count($list['items']));
    }

    public function testUserCannotPatchOrDeleteHabitInV2(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();
        $habit = $this->createHabit($user, 'À ne pas modifier');

        $this->client->jsonRequest(
            'PATCH',
            sprintf('/api/habits/%d', $habit->getId()),
            ['name' => 'Nom mis à jour'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(403);

        $this->client->jsonRequest(
            'DELETE',
            sprintf('/api/habits/%d', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testUserListIsIsolatedFromOtherUsers(): void
    {
        ['user' => $other] = $this->createAuthenticatedUser('other');
        $this->createHabit($other, 'Habit d\'autrui');

        ['token' => $myToken] = $this->createAuthenticatedUser('me');

        $this->client->jsonRequest(
            'GET',
            '/api/habits',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $myToken)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('items', $payload);
        self::assertCount(0, $payload['items']);
    }

    public function testDeletingCategoryKeepsHabitWithNullCategory(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser();
        $category = $this->createCategory('__test__cat-'.uniqid('', true));
        $habit = $this->createHabit($user, 'Habit catégorisée', $category);
        $habitId = $habit->getId();

        $this->entityManager->remove($category);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/habits',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(1, $payload['items']);
        self::assertSame($habitId, $payload['items'][0]['id']);
        self::assertNull($payload['items'][0]['category']);
    }

    public function testAdminCanListAllHabits(): void
    {
        ['user' => $user] = $this->createAuthenticatedUser('regular');
        $this->createHabit($user, 'Habit du user');

        $adminEmail = sprintf('__test__habit-admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $adminToken = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/admin/habits',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $adminToken)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('items', $payload);
        self::assertNotEmpty($payload['items']);
        self::assertArrayHasKey('user', $payload['items'][0]);
    }

    public function testUserCannotAccessAdminHabits(): void
    {
        ['token' => $token] = $this->createAuthenticatedUser();

        $this->client->jsonRequest(
            'GET',
            '/api/admin/habits',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    private function createAuthenticatedUser(string $tag = 'user'): array
    {
        $email = sprintf('__test__habit-%s-%s@habitquest.test', $tag, uniqid('', true));
        $user = $this->createUser($email, 'Habit1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'Habit1234!');

        return ['user' => $user, 'token' => $token];
    }

    private function createCategory(string $name): Category
    {
        $category = (new Category())
            ->setName($name)
            ->setLinkedStat(StatType::FORCE);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createHabit(User $user, string $name, ?Category $category = null): Habit
    {
        $habit = (new Habit())
            ->setUser($user)
            ->setName($name)
            ->setDescription('Habitude de test.')
            ->setXpReward(10)
            ->setGoldReward(5)
            ->setIsActive(true);

        if ($category !== null) {
            $habit->setCategory($category);
        }

        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        return $habit;
    }
}

