<?php

namespace App\Tests\Functional\Api;

use App\Entity\Category;
use App\Entity\QuestCondition;
use App\Enum\QuestKind;
use App\Enum\StatType;
use App\Enum\UserRole;
use App\Repository\QuestConditionRepository;

final class AdminQuestConditionApiTest extends ApiTestCase
{
    public function testUserCannotListConditions(): void
    {
        $email = sprintf('__test__user-%s@habitquest.test', uniqid('', true));
        $this->createUser($email, 'User1234!', UserRole::USER);
        $template = $this->createQuestTemplate('__test__Quest', QuestKind::DAILY, 10);
        $token = $this->authenticate($this->client, $email, 'User1234!');

        $this->client->jsonRequest(
            'GET',
            sprintf('/api/admin/quest-templates/%d/conditions', $template->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateAndListHabitLogsCountCondition(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::DAILY,
            25
        );
        $templateId = $template->getId();
        self::assertNotNull($templateId);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/quest-templates/%d/conditions', $templateId),
            [
                'kind' => 'habit_logs_count',
                'params' => ['count' => 3],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($created);
        self::assertSame('habit_logs_count', $created['condition']['kind'] ?? null);
        self::assertSame(3, $created['condition']['params']['count'] ?? null);

        $this->client->jsonRequest(
            'GET',
            sprintf('/api/admin/quest-templates/%d/conditions', $templateId),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $list = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($list);
        self::assertCount(1, $list['items'] ?? []);
    }

    public function testAdminRejectsInvalidParamsForCategoryLogsCount(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::WEEKLY,
            30
        );

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/quest-templates/%d/conditions', $template->getId()),
            [
                'kind' => 'category_logs_count',
                'params' => ['count' => 2],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminCanCreateCategoryLogsCountWithValidCategory(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $category = (new Category())
            ->setName(sprintf('__test__Cat %s', uniqid('', true)))
            ->setLinkedStat(StatType::FORCE);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::DAILY,
            15
        );

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/quest-templates/%d/conditions', $template->getId()),
            [
                'kind' => 'category_logs_count',
                'params' => [
                    'count' => 5,
                    'categoryId' => $category->getId(),
                ],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(201);

        $repo = static::getContainer()->get(QuestConditionRepository::class);
        $conditions = $repo->findAllForTemplate($template);
        self::assertCount(1, $conditions);
        self::assertInstanceOf(QuestCondition::class, $conditions[0]);
        self::assertSame(5, $conditions[0]->getParams()['count']);
    }

    public function testAdminCanDeleteCondition(): void
    {
        $adminEmail = sprintf('__test__admin-%s@habitquest.test', uniqid('', true));
        $this->createUser($adminEmail, 'Admin1234!', UserRole::ADMIN);
        $token = $this->authenticate($this->client, $adminEmail, 'Admin1234!');

        $template = $this->createQuestTemplate(
            sprintf('__test__Quest %s', uniqid('', true)),
            QuestKind::DAILY,
            5
        );

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/admin/quest-templates/%d/conditions', $template->getId()),
            [
                'kind' => 'xp_gained',
                'params' => ['amount' => 100],
            ],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(201);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $conditionId = $payload['condition']['id'] ?? null;
        self::assertIsInt($conditionId);

        $this->client->jsonRequest(
            'DELETE',
            sprintf('/api/admin/quest-templates/%d/conditions/%d', $template->getId(), $conditionId),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();

        $repo = static::getContainer()->get(QuestConditionRepository::class);
        self::assertSame([], $repo->findAllForTemplate($template));
    }
}

