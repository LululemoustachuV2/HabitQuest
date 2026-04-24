<?php

namespace App\Service;

use App\Dto\CreateEventDto;
use App\Entity\Event;
use App\Entity\EventQuestSelection;
use App\Entity\Notification;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserQuestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

final class EventService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly UserRepository $userRepository,
        private readonly UserQuestRepository $userQuestRepository,
    ) {
    }

    /**
     * MVP : uniquement des événements globaux (diffusés à tous les utilisateurs).
     */
    public function createEventAndAssignQuests(CreateEventDto $dto): array
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return [
                'statusCode' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Utilisateur non authentifié.',
            ];
        }

        $startsAt = new \DateTimeImmutable($dto->startsAt);
        $endsAt = new \DateTimeImmutable($dto->endsAt);

        if ($endsAt <= $startsAt) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'La date de fin doit être strictement postérieure à la date de début.',
            ];
        }

        $event = (new Event())
            ->setCreatedBy($currentUser)
            ->setStartsAt($startsAt)
            ->setEndsAt($endsAt)
            ->setXpReward((int) $dto->eventXpReward);

        $this->entityManager->persist($event);

        $templates = [];
        foreach ($dto->questTemplateIds as $questTemplateId) {
            $template = $this->questTemplateRepository->find((int) $questTemplateId);
            if (!$template instanceof QuestTemplate) {
                return [
                    'statusCode' => Response::HTTP_NOT_FOUND,
                    'message' => sprintf('QuestTemplate introuvable : %s.', $questTemplateId),
                ];
            }

            if ($template->getKind() !== QuestKind::EVENT) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => sprintf('La quête #%d n\'est pas de type "event" et ne peut pas être ajoutée à un événement global.', $questTemplateId),
                ];
            }

            if (!$template->isActive()) {
                return [
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => sprintf('La quête #%d est inactive.', $questTemplateId),
                ];
            }

            $this->entityManager->persist(
                (new EventQuestSelection())
                    ->setEvent($event)
                    ->setQuestTemplate($template)
            );

            $templates[] = $template;
        }

        $users = $this->userRepository->findBy(['role' => UserRole::USER]);
        foreach ($users as $user) {
            foreach ($templates as $template) {
                // Nouvel event : pas de doublon possible, on persiste directement.
                // (Un appel à findOneForUserAndTemplate ici planterait car $event n'est pas encore flushé.)
                $this->entityManager->persist(
                    (new UserQuest())
                        ->setUser($user)
                        ->setQuestTemplate($template)
                        ->setEvent($event)
                );
            }

            $this->entityManager->persist(
                (new Notification())
                    ->setUser($user)
                    ->setTitle('Nouvel événement')
                    ->setBody('Un nouvel événement global est disponible avec de nouvelles quêtes.')
            );
        }

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Événement créé.',
            'event' => [
                'id' => $event->getId(),
                'startsAt' => $startsAt->format(\DateTimeInterface::ATOM),
                'endsAt' => $endsAt->format(\DateTimeInterface::ATOM),
                'eventXpReward' => (int) $dto->eventXpReward,
                'questTemplateIds' => $dto->questTemplateIds,
                'assignedUsersCount' => count($users),
            ],
        ];
    }
}
