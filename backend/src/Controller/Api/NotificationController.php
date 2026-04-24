<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    #[Route('/api/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $notifications = $this->notificationRepository->findRecentForUser($user);

        $items = array_map(
            static fn (Notification $notification): array => [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'body' => $notification->getBody(),
                'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'readAt' => $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
            ],
            $notifications
        );

        return $this->json(['items' => $items]);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notifications_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markAsRead(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $notification = $this->notificationRepository->find($id);
        if (!$notification instanceof Notification) {
            return $this->json(['message' => 'Notification introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Accès interdit à cette notification.'], Response::HTTP_FORBIDDEN);
        }

        $notification->markAsRead();
        $this->entityManager->flush();

        return $this->json(['message' => 'Notification marquée comme lue.']);
    }
}
