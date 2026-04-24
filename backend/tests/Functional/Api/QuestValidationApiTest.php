<?php

namespace App\Tests\Functional\Api;

use App\Enum\QuestKind;
use App\Enum\UserRole;

final class QuestValidationApiTest extends ApiTestCase
{
    public function testUserCanValidateQuestOnce(): void
    {
        $email = sprintf('__test__quest-user-%s@habitquest.test', uniqid('', true));
        $title = sprintf('__test__Lire 15 minutes %s', uniqid('', true));

        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $template = $this->createQuestTemplate($title, QuestKind::DAILY, 40);
        $userQuest = $this->createUserQuest($user, $template);

        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Test validation'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(200, $payload['statusCode'] ?? null);
        self::assertSame(40, $payload['xpAwarded'] ?? null);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Deuxième tentative'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(409);
    }
}
