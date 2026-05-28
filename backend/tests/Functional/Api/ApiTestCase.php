<?php

namespace App\Tests\Functional\Api;

use App\Entity\Item;
use App\Entity\MonsterSequenceStep;
use App\Entity\MonsterTemplate;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\AffinityStat;
use App\Enum\BonusStat;
use App\Enum\QuestKind;
use App\Enum\Rarity;
use App\Enum\UserRole;
use App\Repository\MonsterTemplateRepository;
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
        $this->ensureMonsterCatalogForTests();
        $this->expireAllActiveEvents();
    }

    protected function expireAllActiveEvents(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM user_quests WHERE event_id IS NOT NULL');
        $conn->executeStatement('DELETE FROM event_quest_selections');
        $conn->executeStatement('DELETE FROM events');
        $this->entityManager->clear();
    }

    protected function ensureMonsterCatalogForTests(): void
    {
        $repo = $this->entityManager->getRepository(MonsterTemplate::class);
        if ($repo->findOneByName('__test__Slime QA') instanceof MonsterTemplate) {
            return;
        }

        $item = $this->createItem('__test__Loot QA');
        $itemId = $item->getId();
        self::assertNotNull($itemId);

        $template = (new MonsterTemplate())
            ->setName('__test__Slime QA')
            ->setBaseHp(50)
            ->setLevelMin(1)
            ->setLevelMax(99)
            ->setRarity(Rarity::COMMON)
            ->setAffinityStat(AffinityStat::NEUTRAL)
            ->setLootTable([['itemId' => $itemId, 'weight' => 100]]);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->ensureMonsterInSequence($template);
    }

    protected function ensureMonsterInSequence(MonsterTemplate $template): void
    {
        $existing = $this->entityManager->getRepository(MonsterSequenceStep::class)
            ->findOneBy(['monsterTemplate' => $template]);
        if ($existing instanceof MonsterSequenceStep) {
            return;
        }

        $maxOrder = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(MAX(s.stepOrder), 0)')
            ->from(MonsterSequenceStep::class, 's')
            ->getQuery()
            ->getSingleScalarResult();

        $step = (new MonsterSequenceStep())
            ->setStepOrder($maxOrder + 1)
            ->setMonsterTemplate($template);
        $this->entityManager->persist($step);
        $this->entityManager->flush();
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
            ->setBaseDamage($kind->defaultBaseDamage())
            ->setXpReward($xpReward)
            ->setRequiredLevel($requiredLevel)
            ->setIsActive(true);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }

    protected function createItem(
        string $name,
        Rarity $rarity = Rarity::COMMON,
        int $bonusXpPercent = 0,
        int $bonusGold = 0,
        ?BonusStat $bonusStat = null,
        int $bonusStatValue = 0,
        bool $isSellable = false,
        ?int $shopPrice = null,
    ): Item {
        $item = (new Item())
            ->setName($name)
            ->setDescription('Item de test.')
            ->setRarity($rarity)
            ->setBonusXpPercent($bonusXpPercent)
            ->setBonusGold($bonusGold)
            ->setBonusStat($bonusStat)
            ->setBonusStatValue($bonusStatValue);

        if ($isSellable) {
            $item->setIsSellable(true)->setShopPrice($shopPrice ?? 10);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
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

