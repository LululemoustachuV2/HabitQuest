<?php

namespace App\Tests\Functional\Api;

use App\Entity\Notification;
use App\Enum\UserRole;

final class NotificationApiTest extends ApiTestCase
{
    public function testUserCanListAndMarkNotificationAsRead(): void
    {
        $email = sprintf('notification-user-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Notif1234!', UserRole::USER);

        $notification = (new Notification())
            ->setUser($user)
            ->setTitle('Bienvenue')
            ->setBody('Ton compte vient d\'être créé, bonne aventure !');
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $token = $this->authenticate($this->client, $email, 'Notif1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/notifications',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('items', $payload);
        self::assertNotEmpty($payload['items']);

        $this->client->jsonRequest(
            'POST',
            sprintf('/api/notifications/%d/read', $notification->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
    }
}
