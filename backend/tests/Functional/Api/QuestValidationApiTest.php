<?php

namespace App\Tests\Functional\Api;

use App\Entity\QuestReward;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\InventoryRepository;
use App\Repository\StatRepository;

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
        self::assertArrayHasKey('damageDealt', $payload);
        self::assertIsInt($payload['damageDealt']);
        self::assertArrayHasKey('monsterDied', $payload);
        self::assertIsBool($payload['monsterDied']);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Deuxième tentative'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(409);
    }

    public function testUserGetsComposedRewardInsteadOfTemplateXp(): void
    {
        $email = sprintf('__test__quest-reward-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $item = $this->createItem(sprintf('__test__QuestLoot %s', uniqid('', true)));

        $template = $this->createQuestTemplate(
            sprintf('__test__Rich quest %s', uniqid('', true)),
            QuestKind::DAILY,
            999
        );

        $reward = (new QuestReward())
            ->setQuestTemplate($template)
            ->setXp(12)
            ->setGold(7)
            ->setItem($item);
        $this->entityManager->persist($reward);

        $userQuest = $this->createUserQuest($user, $template);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Validation récompense composée'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(12, $payload['xpAwarded'] ?? null);
        self::assertSame(7, $payload['goldAwarded'] ?? null);
        self::assertSame($item->getId(), $payload['itemGranted']['itemId'] ?? null);

        $refreshedUser = $this->entityManager->find(\App\Entity\User::class, $user->getId());
        self::assertInstanceOf(\App\Entity\User::class, $refreshedUser);
        self::assertSame(12, $refreshedUser->getXp());
        self::assertSame(7, $refreshedUser->getGold());

        $inventoryRepo = static::getContainer()->get(InventoryRepository::class);
        $entries = $inventoryRepo->findAllForUser($refreshedUser);
        self::assertCount(1, $entries);
        self::assertSame($item->getId(), $entries[0]->getItem()->getId());
    }

    public function testComposedRewardAppliesStatBonusesAndLevelUp(): void
    {
        $email = sprintf('__test__quest-stat-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);

        $template = $this->createQuestTemplate(
            sprintf('__test__Stat quest %s', uniqid('', true)),
            QuestKind::DAILY,
            1
        );

        $reward = (new QuestReward())
            ->setQuestTemplate($template)
            ->setXp(100)
            ->setGold(0)
            ->setParams(['stats' => ['force' => 3, 'discipline' => 2]]);
        $this->entityManager->persist($reward);

        $userQuest = $this->createUserQuest($user, $template);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/quests',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();
        $listPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $activeQuest = null;
        foreach ($listPayload['activeByKind']['daily'] ?? [] as $quest) {
            if ($quest['id'] === $userQuest->getId()) {
                $activeQuest = $quest;
                break;
            }
        }
        self::assertIsArray($activeQuest);
        self::assertSame(
            [
                ['stat' => 'force', 'points' => 3],
                ['stat' => 'discipline', 'points' => 2],
            ],
            $activeQuest['statRewards'] ?? null
        );

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Validation bonus stats'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['leveledUp'] ?? false);
        self::assertSame(2, $payload['newLevel'] ?? null);
        self::assertSame(
            [
                ['stat' => 'force', 'points' => 3],
                ['stat' => 'discipline', 'points' => 2],
            ],
            $payload['statRewardsGranted'] ?? null
        );

        $this->entityManager->clear();
        $reloadedUser = $this->entityManager->find(\App\Entity\User::class, $user->getId());
        self::assertInstanceOf(\App\Entity\User::class, $reloadedUser);

        $statRepo = static::getContainer()->get(StatRepository::class);
        $stat = $statRepo->findOneByUser($reloadedUser);
        self::assertNotNull($stat);
        self::assertSame(4, $stat->getForce());
        self::assertSame(3, $stat->getDiscipline());
        self::assertSame(1, $stat->getIntelligence());
        self::assertSame(1, $stat->getCreativity());
    }
}

