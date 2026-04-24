<?php

namespace App\Controller\Api;

use App\Dto\CreateEventDto;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminEventController extends AbstractController
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/events', name: 'api_admin_event_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var CreateEventDto $dto */
            $dto = $this->serializer->deserialize($request->getContent(), CreateEventDto::class, 'json');
        } catch (\Throwable) {
            return $this->json(['message' => 'Corps de requête JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['message' => 'Validation échouée.', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->eventService->createEventAndAssignQuests($dto);
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => 'Erreur interne lors de la création de l\'événement.',
                'error' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($result, $result['statusCode']);
    }
}
