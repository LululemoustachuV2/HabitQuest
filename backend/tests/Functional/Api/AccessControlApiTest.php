<?php

namespace App\Tests\Functional\Api;

use App\Enum\UserRole;

final class AccessControlApiTest extends ApiTestCase
{
    public function testUserCannotAccessAdminQuestTemplatesEndpoint(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));

        $this->createUser($email, 'User1234!', UserRole::USER);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/admin/quest-templates',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }
}
