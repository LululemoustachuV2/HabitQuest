<?php

namespace App\Tests\Functional\Api;

use App\Enum\QuestKind;
use App\Enum\UserRole;

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
}
