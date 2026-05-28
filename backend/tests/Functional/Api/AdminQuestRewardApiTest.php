<?php

namespace App\Tests\Functional\Api;

use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\QuestRewardRepository;

final class AdminQuestRewardApiTest extends ApiTestCase
{
    public function testUserCannotUpsertReward(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $template = $this->createQuestTemplate('__test__Quest', QuestKind::DAILY, 10);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'PUT',
            sprintf('/api/admin/quest-templates/%d/reward', $template->getId()),
            ['xp' => 10, 'gold' => 5],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanUpsertAndGetComposedReward(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $item = $this->createItem(sprintf('__test__RewardItem %s', uniqid('', true)));
        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::DAILY,
            99
        );

        $this->client->jsonRequest(
            'PUT',
            sprintf('/api/admin/quest-templates/%d/reward', $template->getId()),
            [
                'xp' => 50,
                'gold' => 20,
                'itemId' => $item->getId(),
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $this->client->jsonRequest(
            'GET',
            sprintf('/api/admin/quest-templates/%d/reward', $template->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(50, $payload['reward']['xp'] ?? null);
        self::assertSame(20, $payload['reward']['gold'] ?? null);
        self::assertSame($item->getId(), $payload['reward']['itemId'] ?? null);

        $repo = static::getContainer()->get(QuestRewardRepository::class);
        self::assertNotNull($repo->findOneByQuestTemplate($template));
    }

    public function testAdminRejectsUnknownItem(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::DAILY,
            10
        );

        $this->client->jsonRequest(
            'PUT',
            sprintf('/api/admin/quest-templates/%d/reward', $template->getId()),
            [
                'xp' => 10,
                'gold' => 0,
                'itemId' => 999999,
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }
}

