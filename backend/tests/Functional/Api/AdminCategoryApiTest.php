<?php

namespace App\Tests\Functional\Api;

use App\Entity\Category;
use App\Enum\StatType;
use App\Enum\UserRole;
use App\Repository\CategoryRepository;

final class AdminCategoryApiTest extends ApiTestCase
{
    public function testUserCannotAccessAdminCategoryEndpoint(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/admin/categories',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testUserCannotCreateCategory(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/categories',
            [
                'name' => sprintf('__test__Cat %s', uniqid('', true)),
                'linkedStat' => StatType::FORCE->value,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateCategory(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $name = sprintf('__test__Cat %s', uniqid('', true));

        $this->client->jsonRequest(
            'POST',
            '/api/admin/categories',
            [
                'name' => $name,
                'linkedStat' => StatType::INTELLIGENCE->value,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('category', $payload);
        self::assertSame($name, $payload['category']['name']);
        self::assertSame(StatType::INTELLIGENCE->value, $payload['category']['linkedStat']);

        $repo = static::getContainer()->get(CategoryRepository::class);
        self::assertInstanceOf(Category::class, $repo->findOneByName($name));
    }

    public function testAdminCannotCreateDuplicateCategoryName(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $name = sprintf('__test__Cat %s', uniqid('', true));

        $body = [
            'name' => $name,
            'linkedStat' => StatType::DISCIPLINE->value,
        ];

        $this->client->jsonRequest(
            'POST',
            '/api/admin/categories',
            $body,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(201);

        $this->client->jsonRequest(
            'POST',
            '/api/admin/categories',
            $body,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(409);
    }

    public function testAdminRejectsInvalidLinkedStat(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/categories',
            [
                'name' => sprintf('__test__Cat %s', uniqid('', true)),
                'linkedStat' => 'charisma',
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }
}

