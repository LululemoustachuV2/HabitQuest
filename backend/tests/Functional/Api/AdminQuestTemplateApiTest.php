<?php

namespace App\Tests\Functional\Api;

use App\Enum\UserRole;

final class AdminQuestTemplateApiTest extends ApiTestCase
{
    public function testAdminCanCreateQuestTemplate(): void
    {
        $adminEmail = sprintf('__test__admin-qt-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $this->client->jsonRequest(
            'POST',
            '/api/admin/quest-templates',
            [
                'kind' => 'daily',
                'title' => sprintf('__test__ Admin quest %s', uniqid('', true)),
                'description' => 'Quête créée depuis l admin.',
                'xpReward' => 50,
                'requiredLevel' => 1,
                'isActive' => true,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('template', $payload);
        self::assertSame('daily', $payload['template']['kind'] ?? null);
    }
}

