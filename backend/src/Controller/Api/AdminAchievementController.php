<?php

namespace App\Controller\Api;

use App\Dto\AchievementDto;
use App\Service\AchievementAdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminAchievementController extends AbstractController
{
    public function __construct(
        private readonly AchievementAdminService $achievementAdminService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/achievements', name: 'api_admin_achievements_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $result = $this->achievementAdminService->list();

        return $this->apiJson($result);
    }

    #[Route('/api/admin/achievements', name: 'api_admin_achievements_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, AchievementDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        return $this->apiJson($this->achievementAdminService->create($dto));
    }

    #[Route('/api/admin/achievements/{id}', name: 'api_admin_achievements_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, AchievementDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        return $this->apiJson($this->achievementAdminService->update($id, $dto));
    }

    #[Route('/api/admin/achievements/{id}', name: 'api_admin_achievements_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        return $this->apiJson($this->achievementAdminService->delete($id));
    }

    private function apiJson(array $result): JsonResponse
    {
        $status = $result['statusCode'] ?? Response::HTTP_OK;
        unset($result['statusCode']);

        return $this->json($result, $status);
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

