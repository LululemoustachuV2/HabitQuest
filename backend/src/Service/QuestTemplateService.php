<?php

namespace App\Service;

use App\Dto\QuestTemplateDto;
use App\Entity\Event;
use App\Entity\EventQuestSelection;
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
use Symfony\Component\HttpFoundation\Response;

final class QuestTemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly EventRepository $eventRepository,
        private readonly UserRepository $userRepository,
        private readonly UserQuestRepository $userQuestRepository,
    ) {
    }

    public function createTemplate(QuestTemplateDto $dto): array
    {
        $extraError = $this->validateEventReward($dto);
        if ($extraError !== null) {
            return $extraError;
        }

        $template = (new QuestTemplate())
            ->setKind(QuestKind::from($dto->kind))
            ->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setXpReward((int) $dto->xpReward)
            ->setRequiredLevel($dto->requiredLevel)
            ->setIsActive($dto->isActive);

        $this->entityManager->persist($template);
        $this->assignTemplateToUsersIfNeeded($template);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_CREATED,
            'message' => 'Modèle de quête créé.',
            'template' => $this->toArray($template),
        ];
    }

    public function updateTemplate(int $id, QuestTemplateDto $dto): array
    {
        $extraError = $this->validateEventReward($dto);
        if ($extraError !== null) {
            return $extraError;
        }

        $template = $this->questTemplateRepository->find($id);
        if (!$template instanceof QuestTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $template
            ->setKind(QuestKind::from($dto->kind))
            ->setTitle($dto->title)
            ->setDescription($dto->description)
            ->setXpReward((int) $dto->xpReward)
            ->setRequiredLevel($dto->requiredLevel)
            ->setIsActive($dto->isActive);

        $this->assignTemplateToUsersIfNeeded($template);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Modèle de quête mis à jour.',
            'template' => $this->toArray($template),
        ];
    }

    public function setTemplateActive(int $id, bool $isActive): array
    {
        $template = $this->questTemplateRepository->find($id);
        if (!$template instanceof QuestTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $template->setIsActive($isActive);
        if ($isActive) {
            $this->assignTemplateToUsersIfNeeded($template);
        }
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => $isActive ? 'Modèle de quête activé.' : 'Modèle de quête désactivé.',
            'template' => $this->toArray($template),
        ];
    }

    public function getDeleteImpact(int $id): array
    {
        $template = $this->questTemplateRepository->find($id);
        if (!$template instanceof QuestTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $selectionRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(s.event) AS eventId')
            ->from(EventQuestSelection::class, 's')
            ->where('s.questTemplate = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getArrayResult();

        $eventIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['eventId'], $selectionRows)));

        $events = [];
        if ($eventIds !== []) {
            $eventEntities = $this->eventRepository->findBy(['id' => $eventIds]);
            $events = array_map(
                static fn (Event $event): array => [
                    'id' => $event->getId(),
                    'startsAt' => $event->getStartsAt()->format(\DateTimeInterface::ATOM),
                    'endsAt' => $event->getEndsAt()->format(\DateTimeInterface::ATOM),
                ],
                $eventEntities
            );
        }

        $linkedUserQuestsCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(uq.id)')
            ->from(UserQuest::class, 'uq')
            ->where('uq.questTemplate = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'statusCode' => Response::HTTP_OK,
            'template' => $this->toArray($template),
            'impact' => [
                'eventsToDelete' => $events,
                'eventsToDeleteCount' => count($events),
                'userQuestsToDeleteCount' => $linkedUserQuestsCount,
            ],
        ];
    }

    public function deleteTemplateAndLinkedEvents(int $id): array
    {
        $impact = $this->getDeleteImpact($id);
        if (($impact['statusCode'] ?? Response::HTTP_INTERNAL_SERVER_ERROR) !== Response::HTTP_OK) {
            return $impact;
        }

        $template = $this->questTemplateRepository->find($id);
        if (!$template instanceof QuestTemplate) {
            return [
                'statusCode' => Response::HTTP_NOT_FOUND,
                'message' => 'Modèle de quête introuvable.',
            ];
        }

        $eventIds = array_map(static fn (array $event): int => (int) $event['id'], $impact['impact']['eventsToDelete']);

        if ($eventIds !== []) {
            $events = $this->eventRepository->findBy(['id' => $eventIds]);
            foreach ($events as $event) {
                $eventUserQuests = $this->userQuestRepository->findBy(['event' => $event]);
                foreach ($eventUserQuests as $eventUserQuest) {
                    $this->entityManager->remove($eventUserQuest);
                }
                $this->entityManager->remove($event);
            }
        }

        $templateUserQuests = $this->userQuestRepository->findBy(['questTemplate' => $template]);
        foreach ($templateUserQuests as $templateUserQuest) {
            $this->entityManager->remove($templateUserQuest);
        }

        $this->entityManager->remove($template);
        $this->entityManager->flush();

        return [
            'statusCode' => Response::HTTP_OK,
            'message' => 'Modèle de quête supprimé avec ses impacts liés.',
            'impact' => $impact['impact'],
        ];
    }

    public function toArray(QuestTemplate $template): array
    {
        return [
            'id' => $template->getId(),
            'kind' => $template->getKind()->value,
            'title' => $template->getTitle(),
            'description' => $template->getDescription(),
            'xpReward' => $template->getXpReward(),
            'requiredLevel' => $template->getRequiredLevel(),
            'isActive' => $template->isActive(),
        ];
    }

    private function validateEventReward(QuestTemplateDto $dto): ?array
    {
        if ($dto->kind === QuestKind::EVENT->value && (int) $dto->xpReward > 0) {
            return [
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Les quêtes "event" ne peuvent pas avoir de récompense XP propre (le gain est porté par l\'événement).',
            ];
        }

        return null;
    }

    private function assignTemplateToUsersIfNeeded(QuestTemplate $template): void
    {
        if (!$template->isActive()) {
            return;
        }

        if ($template->getKind() === QuestKind::EVENT) {
            return;
        }

        $users = $this->userRepository->findBy(['role' => UserRole::USER]);
        foreach ($users as $user) {
            $existing = $this->userQuestRepository->findOneForUserAndTemplate($user, $template, null);
            if ($existing instanceof UserQuest) {
                continue;
            }

            $this->entityManager->persist(
                (new UserQuest())
                    ->setUser($user)
                    ->setQuestTemplate($template)
            );
        }
    }
}
