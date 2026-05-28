<?php

namespace App\Controller\Api;

use App\Dto\AdminGrantRequestDto;
use App\Dto\AdminModerationReasonDto;
use App\Entity\User;
use App\Service\AdminUserModerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly AdminUserModerationService $moderationService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/users/{id}/grant-xp', name: 'api_admin_user_grant_xp', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function grantXp(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, AdminGrantRequestDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $admin = $this->getUser();
        $result = $this->moderationService->grantXp(
            $admin,
            $id,
            (int) $dto->amount,
            trim((string) $dto->reason),
        );

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/users/{id}/grant-gold', name: 'api_admin_user_grant_gold', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function grantGold(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, AdminGrantRequestDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $admin = $this->getUser();
        $result = $this->moderationService->grantGold(
            $admin,
            $id,
            (int) $dto->amount,
            trim((string) $dto->reason),
        );

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/users/{id}/respawn-monster', name: 'api_admin_user_respawn_monster', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function respawnMonster(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, AdminModerationReasonDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $admin = $this->getUser();
        $result = $this->moderationService->respawnMonster(
            $admin,
            $id,
            trim((string) $dto->reason),
        );

        return $this->json($result, $result['statusCode']);
    }

    private function deserializeAndValidate(Request $request, string $dtoClass): object
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, 'json');
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

        return $dto;
    }
}

