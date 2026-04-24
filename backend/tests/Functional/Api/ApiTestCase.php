<?php

namespace App\Tests\Functional\Api;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->client->disableReboot();
    }

    protected function createUser(string $email, string $password, UserRole $role = UserRole::USER): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setRole($role);

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createQuestTemplate(
        string $title,
        QuestKind $kind = QuestKind::DAILY,
        int $xpReward = 50,
        int $requiredLevel = 1,
    ): QuestTemplate {
        $template = (new QuestTemplate())
            ->setTitle($title)
            ->setDescription('Modèle de quête de test.')
            ->setKind($kind)
            ->setXpReward($xpReward)
            ->setRequiredLevel($requiredLevel)
            ->setIsActive(true);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }

    protected function createUserQuest(User $user, QuestTemplate $template): UserQuest
    {
        $userQuest = (new UserQuest())
            ->setUser($user)
            ->setQuestTemplate($template);

        $this->entityManager->persist($userQuest);
        $this->entityManager->flush();

        return $userQuest;
    }

    protected function authenticate(KernelBrowser $client, string $email, string $password): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('token', $payload);

        return (string) $payload['token'];
    }
}
