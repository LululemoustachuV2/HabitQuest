<?php

namespace App\Controller\Api;

use App\Dto\QuestRewardDto;
use App\Service\QuestRewardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminQuestRewardController extends AbstractController
{
    public function __construct(
        private readonly QuestRewardService $questRewardService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/reward',
        name: 'api_admin_quest_reward_get',
        methods: ['GET'],
        requirements: ['templateId' => '\d+'],
    )]
    public function get(int $templateId): JsonResponse
    {
        $result = $this->questRewardService->getForTemplate($templateId);

        return $this->json($result, $result['statusCode']);
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/reward',
        name: 'api_admin_quest_reward_upsert',
        methods: ['PUT', 'POST'],
        requirements: ['templateId' => '\d+'],
    )]
    public function upsert(int $templateId, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, QuestRewardDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->questRewardService->upsertReward($templateId, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/reward',
        name: 'api_admin_quest_reward_delete',
        methods: ['DELETE'],
        requirements: ['templateId' => '\d+'],
    )]
    public function delete(int $templateId): JsonResponse
    {
        $result = $this->questRewardService->deleteReward($templateId);

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

