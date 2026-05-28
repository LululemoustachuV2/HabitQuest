<?php

namespace App\Controller\Api;

use App\Entity\Habit;
use App\Entity\User;
use App\Repository\HabitRepository;
use App\Service\HabitLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HabitLogController extends AbstractController
{
    private const NOTE_MAX_LENGTH = 2000;

    public function __construct(
        private readonly HabitLogService $habitLogService,
        private readonly HabitRepository $habitRepository,
        #[Autowire(service: 'limiter.habit_log')]
        private readonly RateLimiterFactory $habitLogLimiter,
    ) {
    }

    #[Route(
        '/api/habits/{id}/log',
        name: 'api_habits_log',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function log(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();

        $limit = $this->habitLogLimiter->create($user->getUserIdentifier());
        if (!$limit->consume()->isAccepted()) {
            return $this->json(
                ['message' => 'Trop de logs d\'habitudes. Réessayez dans une minute.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $habit = $this->habitRepository->find($id);
        if (!$habit instanceof Habit) {
            return $this->json(['message' => 'Habitude introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $note = $this->extractNote($request);
        if ($note instanceof JsonResponse) {
            return $note;
        }

        $result = $this->habitLogService->logHabit($habit, $user, $note);

        return $this->json($result, $result['statusCode']);
    }

    private function extractNote(Request $request): string|null|JsonResponse
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['message' => 'Corps de requête JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($decoded)) {
            return $this->json(['message' => 'Corps de requête JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('note', $decoded) || $decoded['note'] === null) {
            return null;
        }

        if (!is_string($decoded['note'])) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors' => ['note' => 'La note doit être une chaîne de caractères.'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (mb_strlen($decoded['note']) > self::NOTE_MAX_LENGTH) {
            return $this->json([
                'message' => 'Validation échouée.',
                'errors' => ['note' => sprintf('La note ne peut pas dépasser %d caractères.', self::NOTE_MAX_LENGTH)],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $decoded['note'];
    }
}

