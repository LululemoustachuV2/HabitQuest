<?php

namespace App\Tests\Functional\Api;

use App\Entity\Habit;
use App\Entity\QuestCondition;
use App\Entity\QuestReward;
use App\Entity\User;
use App\Enum\QuestKind;
use App\Enum\UserQuestStatus;
use App\Enum\UserRole;
use App\Repository\UserQuestRepository;

final class QuestProgressApiTest extends ApiTestCase
{
    public function testHabitLogIncrementsQuestProgressAndAutoCompletes(): void
    {
        $email = sprintf('__test__quest-progress-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);

        $template = $this->createQuestTemplate(
            sprintf('__test__Log 2 fois %s', uniqid('', true)),
            QuestKind::DAILY,
            30
        );

        $condition = (new QuestCondition())
            ->setQuestTemplate($template)
            ->setKind(\App\Enum\QuestConditionKind::HABIT_LOGS_COUNT)
            ->setParams(['count' => 2]);
        $this->entityManager->persist($condition);

        $userQuest = $this->createUserQuest($user, $template);
        $userQuest->setProgress([
            'conditions' => [[
                'conditionId' => null,
                'kind' => 'habit_logs_count',
                'current' => 0,
                'target' => 2,
                'satisfied' => false,
            ]],
            'overall' => ['current' => 0, 'target' => 2],
        ]);

        $habit = (new Habit())
            ->setUser($user)
            ->setName('__test__Habit progress')
            ->setDescription('Test')
            ->setXpReward(10)
            ->setGoldReward(0)
            ->setIsActive(true);
        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        $conditionId = $condition->getId();
        self::assertNotNull($conditionId);
        $progress = $userQuest->getProgress();
        $progress['conditions'][0]['conditionId'] = $conditionId;
        $userQuest->setProgress($progress);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $uqRepo = static::getContainer()->get(UserQuestRepository::class);
        $afterFirst = $uqRepo->find($userQuest->getId());
        self::assertNotNull($afterFirst);
        self::assertSame(UserQuestStatus::IN_PROGRESS, $afterFirst->getStatus());
        self::assertSame(1, $afterFirst->getProgress()['overall']['current'] ?? null);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $afterSecond = $uqRepo->find($userQuest->getId());
        self::assertNotNull($afterSecond);
        self::assertSame(UserQuestStatus::COMPLETED, $afterSecond->getStatus());
        self::assertTrue($afterSecond->isValidated());

        $this->client->jsonRequest(
            'GET',
            '/api/quests',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseIsSuccessful();
        $list = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($list);
        $completed = $list['completed'] ?? [];
        self::assertNotEmpty($completed);
        $found = false;
        foreach ($completed as $entry) {
            if (($entry['id'] ?? null) === $userQuest->getId()) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'La quête auto-complétée doit apparaître dans completed.');
    }

    public function testManualValidationRejectedWhenTemplateHasConditions(): void
    {
        $email = sprintf('__test__quest-manual-block-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $template = $this->createQuestTemplate(
            sprintf('__test__Conditional %s', uniqid('', true)),
            QuestKind::DAILY,
            20
        );

        $this->entityManager->persist(
            (new QuestCondition())
                ->setQuestTemplate($template)
                ->setKind(\App\Enum\QuestConditionKind::HABIT_LOGS_COUNT)
                ->setParams(['count' => 5])
        );
        $userQuest = $this->createUserQuest($user, $template);
        $token = $this->authenticate($this->client, $email, 'Quest1234!');

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/quests/%d/validate', $userQuest->getId()),
            ['comment' => 'Tentative manuelle'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testGetQuestsIncludesProgressForConditionalQuest(): void
    {
        $email = sprintf('__test__quest-list-progress-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $template = $this->createQuestTemplate(
            sprintf('__test__XP quest %s', uniqid('', true)),
            QuestKind::DAILY,
            10
        );

        $this->entityManager->persist(
            (new QuestCondition())
                ->setQuestTemplate($template)
                ->setKind(\App\Enum\QuestConditionKind::XP_GAINED)
                ->setParams(['amount' => 50])
        );
        $userQuest = $this->createUserQuest($user, $template);
        $userQuest->setProgress([
            'conditions' => [[
                'conditionId' => 1,
                'kind' => 'xp_gained',
                'current' => 10,
                'target' => 50,
                'satisfied' => false,
            ]],
            'overall' => ['current' => 10, 'target' => 50],
        ]);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Quest1234!');
        $this->client->jsonRequest(
            'GET',
            '/api/quests',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $active = $payload['active'] ?? [];
        $match = null;
        foreach ($active as $quest) {
            if (($quest['id'] ?? null) === $userQuest->getId()) {
                $match = $quest;
                break;
            }
        }

        self::assertIsArray($match);
        self::assertTrue($match['hasConditions'] ?? false);
        self::assertSame(10, $match['progress']['current'] ?? null);
        self::assertSame(50, $match['progress']['target'] ?? null);
    }

    public function testAutoCompleteAppliesComposedQuestReward(): void
    {
        $email = sprintf('__test__quest-auto-reward-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Quest1234!', UserRole::USER);
        $item = $this->createItem(sprintf('__test__AutoLoot %s', uniqid('', true)));

        $template = $this->createQuestTemplate(
            sprintf('__test__Auto reward %s', uniqid('', true)),
            QuestKind::DAILY,
            500
        );

        $this->entityManager->persist(
            (new QuestCondition())
                ->setQuestTemplate($template)
                ->setKind(\App\Enum\QuestConditionKind::HABIT_LOGS_COUNT)
                ->setParams(['count' => 1])
        );
        $this->entityManager->persist(
            (new QuestReward())
                ->setQuestTemplate($template)
                ->setXp(8)
                ->setGold(3)
                ->setItem($item)
        );

        $userQuest = $this->createUserQuest($user, $template);
        $habit = (new Habit())
            ->setUser($user)
            ->setName('__test__One log')
            ->setDescription('Test')
            ->setXpReward(5)
            ->setGoldReward(0)
            ->setIsActive(true);
        $this->entityManager->persist($habit);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Quest1234!');
        $this->client->jsonRequest(
            'POST',
            sprintf('/api/habits/%d/log', $habit->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );
        self::assertResponseStatusCodeSame(201);

        $this->entityManager->clear();
        $refreshedUser = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $refreshedUser);
        self::assertSame(13, $refreshedUser->getXp());
        self::assertSame(3, $refreshedUser->getGold());
    }
}

