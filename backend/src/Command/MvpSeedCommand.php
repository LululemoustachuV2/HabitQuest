<?php

namespace App\Command;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserQuestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed:mvp', description: 'Crée des données de base pour tester le MVP backend.')]
final class MvpSeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly UserQuestRepository $userQuestRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->userRepository->findOneByEmail('admin@habitquest.dev');
        if (!$admin instanceof User) {
            $admin = (new User())
                ->setEmail('admin@habitquest.dev')
                ->setRole(UserRole::ADMIN);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
            $this->entityManager->persist($admin);
        }

        $user = $this->userRepository->findOneByEmail('user@habitquest.dev');
        if (!$user instanceof User) {
            $user = (new User())
                ->setEmail('user@habitquest.dev')
                ->setRole(UserRole::USER);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'User123!'));
            $this->entityManager->persist($user);
        }

        $template = $this->questTemplateRepository->findOneBy(['title' => 'Lire 10 minutes']);
        if (!$template instanceof QuestTemplate) {
            $template = (new QuestTemplate())
                ->setKind(QuestKind::DAILY)
                ->setTitle('Lire 10 minutes')
                ->setDescription('Lire au moins 10 minutes dans la journée.')
                ->setXpReward(50)
                ->setIsActive(true);
            $this->entityManager->persist($template);
        }

        $this->entityManager->flush();

        $userQuest = $this->userQuestRepository->findOneBy([
            'user' => $user,
            'questTemplate' => $template,
        ]);

        if (!$userQuest instanceof UserQuest) {
            $userQuest = (new UserQuest())
                ->setUser($user)
                ->setQuestTemplate($template);
            $this->entityManager->persist($userQuest);
            $this->entityManager->flush();
        }

        $output->writeln('Seed MVP effectué avec succès.');
        $output->writeln('- admin : admin@habitquest.dev / Admin123!');
        $output->writeln('- user  : user@habitquest.dev / User123!');
        $output->writeln(sprintf('- id de quête utilisateur de test : %d', $userQuest->getId()));

        return Command::SUCCESS;
    }
}
