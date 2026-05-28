<?php

namespace App\Tests\Functional\Api;

use App\Entity\Notification;
use App\Enum\NotificationSeverity;
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

    public function testListReturnsSeverityAndIsFullscreen(): void
    {
        $email = sprintf('notification-fields-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Notif1234!', UserRole::USER);

        $notification = (new Notification())
            ->setUser($user)
            ->setTitle('Alerte event')
            ->setBody('Un event critique vient de demarrer.')
            ->setSeverity(NotificationSeverity::URGENT)
            ->setIsFullscreen(true);
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
        self::assertIsArray($payload['items'] ?? null);
        self::assertNotEmpty($payload['items']);

        $item = $payload['items'][0];
        self::assertSame('urgent', $item['severity']);
        self::assertTrue($item['isFullscreen']);
        self::assertNull($item['readAt']);
    }

    public function testNewNotificationHasDefaultSeverityAndIsNotFullscreen(): void
    {
        $email = sprintf('notification-defaults-%s@habitquest.test', uniqid('', true));
        $user = $this->createUser($email, 'Notif1234!', UserRole::USER);

        $notification = (new Notification())
            ->setUser($user)
            ->setTitle('Info')
            ->setBody('Message informatif.');
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        self::assertSame(NotificationSeverity::INFO, $notification->getSeverity());
        self::assertFalse($notification->isFullscreen());

        $token = $this->authenticate($this->client, $email, 'Notif1234!');

        $this->client->jsonRequest(
            'GET',
            '/api/notifications',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token)]
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $item = $payload['items'][0];
        self::assertSame('info', $item['severity']);
        self::assertFalse($item['isFullscreen']);
    }
}

