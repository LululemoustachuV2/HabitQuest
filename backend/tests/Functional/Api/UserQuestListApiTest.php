<?php

namespace App\Tests\Functional\Api;

use App\Enum\QuestKind;
use App\Enum\UserRole;

final class UserQuestListApiTest extends ApiTestCase
{
    public function testStaleDailyQuestIsRecycledAndNewInstanceAppearsInActiveList(): void
    {
        $email = sprintf('__test__quest-expired-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $template = $this->createQuestTemplate(
            sprintf('__test__Expired daily %s', uniqid('', true)),
            QuestKind::DAILY,
            10
        );

        $userQuest = $this->createUserQuest($user, $template);
        $userQuest->setStartedAt(
            new \DateTimeImmutable('yesterday', new \DateTimeZone('Europe/Paris'))
        );
        $this->entityManager->flush();

        $staleQuestId = $userQuest->getId();
        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/quests',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);

        $idsInActive = array_map(
            static fn (array $q): int => (int) $q['id'],
            array_merge(
                $payload['active'] ?? [],
                $payload['activeByKind']['daily'] ?? [],
                $payload['eventQuests'] ?? []
            )
        );
        self::assertNotContains($staleQuestId, $idsInActive);
        self::assertSame([], $payload['expired'] ?? []);

        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(\App\Entity\UserQuest::class, $staleQuestId));

        $titlesInDaily = array_map(
            static fn (array $q): string => (string) $q['title'],
            $payload['activeByKind']['daily'] ?? []
        );
        self::assertContains($template->getTitle(), $titlesInDaily);
    }
}

