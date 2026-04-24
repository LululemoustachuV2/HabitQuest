<?php

namespace App\Tests\Functional\Api;

use App\Enum\QuestKind;
use App\Enum\UserRole;

final class ProgressionQuestApiTest extends ApiTestCase
{
    public function testProgressionQuestValidationFailsWhenLevelIsInsufficient(): void
    {
        $email = sprintf('__test__progression-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Progress1234!', UserRole::USER);

        $template = $this->createQuestTemplate(
            sprintf('__test__Story quest %s', uniqid('', true)),
            QuestKind::PROGRESSION,
            80,
            3
        );
        $userQuest = $this->createUserQuest($user, $template);

        $token = $this->authenticate($this->client, $email, 'Progress1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Tentative en-dessous du niveau requis'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertStringContainsString('Niveau insuffisant', (string) ($payload['message'] ?? ''));
    }
}
