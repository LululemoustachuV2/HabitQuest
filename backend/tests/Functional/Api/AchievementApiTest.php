<?php

namespace App\Tests\Functional\Api;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Enum\AchievementCode;
use App\Repository\AchievementRepository;

final class AchievementApiTest extends ApiTestCase
{
    public function testAnonymousCannotListAchievements(): void
    {
        $this->client->request('GET', '/api/achievements');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListReturnsCatalogWithLockedAndUnlocked(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAuthenticatedUser('achievements-list');

        $achievement = $this->getAchievement(AchievementCode::FIRST_QUEST_VALIDATED);
        $this->entityManager->persist(new UserAchievement($user, $achievement));
        $this->entityManager->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/achievements',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('achievements', $payload);
        self::assertGreaterThanOrEqual(3, count($payload['achievements']));

        $firstLog = null;
        foreach ($payload['achievements'] as $row) {
            if (($row['code'] ?? '') === AchievementCode::FIRST_QUEST_VALIDATED->value) {
                $firstLog = $row;
                break;
            }
        }

        self::assertIsArray($firstLog);
        self::assertTrue($firstLog['unlocked']);
        self::assertNotNull($firstLog['unlockedAt']);
    }

    private function createAuthenticatedUser(string $prefix): array
    {
        $email = sprintf('%s-%s@example.com', $prefix, uniqid('', true));
        $user = $this->createUser($email, 'password123');
        $token = $this->authenticate($this->client, $email, 'password123');

        return ['user' => $user, 'token' => $token];
    }

    private function getAchievement(AchievementCode $code): Achievement
    {
        $repo = $this->entityManager->getRepository(Achievement::class);
        $achievement = $repo->findOneByCode($code);
        self::assertInstanceOf(Achievement::class, $achievement);

        return $achievement;
    }
}

