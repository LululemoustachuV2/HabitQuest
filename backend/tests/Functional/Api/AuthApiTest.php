<?php

namespace App\Tests\Functional\Api;

final class AuthApiTest extends ApiTestCase
{
    public function testRegisterThenLoginReturnsToken(): void
    {
        $email = sprintf('__test__new-user-%s@habitquest.test', uniqid('', true));

        $this->client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => 'Test1234!',
        ]);

        self::assertResponseStatusCodeSame(201);

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'Test1234!',
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('token', $payload);
        self::assertNotEmpty($payload['token']);
    }
}
