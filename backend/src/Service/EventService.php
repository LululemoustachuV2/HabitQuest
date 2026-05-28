<?php

namespace App\Service;

use App\Dto\CreateEventDto;
use App\Dto\UpdateEventDto;
use App\Entity\Event;
use App\Entity\EventQuestSelection;
use App\Entity\Notification;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestKind;
use App\Enum\UserRole;
use App\Repository\EventRepository;
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
        private readonly EventRepository $eventRepository,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly UserRepository $userRepository,
        private readonly UserQuestRepository $userQuestRepository,
    ) {
    }

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
            ->setXpReward((int) $dto->eventXpReward)
            ->setXpMultiplier($dto->xpMultiplier ?? 1.0)
            ->setGoldMultiplier($dto->goldMultiplier ?? 1.0)
            ->setBonusRules($dto->bonusRules);

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
            'event' => $this->serializeEvent($event, $dto->questTemplateIds, count($users)),
        ];
    }

    public function updateEvent(int $eventId, UpdateEventDto $dto, array $presentKeys): array
    {
        $event = $this->eventRepository->find($eventId);
        if (!$event instanceof Event) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Événement introuvable.',
            ];
        }

        if (in_array('startsAt', $presentKeys, true) && $dto->startsAt !== null) {
            $event->setStartsAt(new \DateTimeImmutable($dto->startsAt));
        }
        if (in_array('endsAt', $presentKeys, true) && $dto->endsAt !== null) {
            $event->setEndsAt(new \DateTimeImmutable($dto->endsAt));
        }
        if (in_array('eventXpReward', $presentKeys, true) && $dto->eventXpReward !== null) {
            $event->setXpReward($dto->eventXpReward);
        }
        if (in_array('xpMultiplier', $presentKeys, true) && $dto->xpMultiplier !== null) {
            $event->setXpMultiplier($dto->xpMultiplier);
        }
        if (in_array('goldMultiplier', $presentKeys, true) && $dto->goldMultiplier !== null) {
            $event->setGoldMultiplier($dto->goldMultiplier);
        }
        if (in_array('bonusRules', $presentKeys, true)) {
            $event->setBonusRules($dto->bonusRules);
        }

        if ($event->getEndsAt() <= $event->getStartsAt()) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'La date de fin doit être strictement postérieure à la date de début.',
            ];
        }

        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Événement mis à jour.',
            'event' => $this->serializeEvent($event),
        ];
    }

    private function serializeEvent(Event $event, ?array $questTemplateIds = null, ?int $assignedUsersCount = null): array
    {
        $payload = [
            'id' => $event->getId(),
            'startsAt' => $event->getStartsAt()->format(\DateTimeInterface::ATOM),
            'endsAt' => $event->getEndsAt()->format(\DateTimeInterface::ATOM),
            'eventXpReward' => $event->getXpReward(),
            'xpMultiplier' => $event->getXpMultiplier(),
            'goldMultiplier' => $event->getGoldMultiplier(),
            'bonusRules' => $event->getBonusRules(),
        ];

        if ($questTemplateIds !== null) {
            $payload['questTemplateIds'] = $questTemplateIds;
        }
        if ($assignedUsersCount !== null) {
            $payload['assignedUsersCount'] = $assignedUsersCount;
        }

        return $payload;
    }
}

