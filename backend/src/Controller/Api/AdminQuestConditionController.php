<?php

namespace App\Controller\Api;

use App\Dto\QuestConditionDto;
use App\Service\QuestConditionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminQuestConditionController extends AbstractController
{
    public function __construct(
        private readonly QuestConditionService $questConditionService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/conditions',
        name: 'api_admin_quest_condition_list',
        methods: ['GET'],
        requirements: ['templateId' => '\d+'],
    )]
    public function list(int $templateId): JsonResponse
    {
        $result = $this->questConditionService->listForTemplate($templateId);

        return $this->json($result, $result['statusCode']);
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/conditions',
        name: 'api_admin_quest_condition_create',
        methods: ['POST'],
        requirements: ['templateId' => '\d+'],
    )]
    public function create(int $templateId, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, QuestConditionDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->questConditionService->createCondition($templateId, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/conditions/{conditionId}',
        name: 'api_admin_quest_condition_update',
        methods: ['PUT'],
        requirements: ['templateId' => '\d+', 'conditionId' => '\d+'],
    )]
    public function update(int $templateId, int $conditionId, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, QuestConditionDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->questConditionService->updateCondition($templateId, $conditionId, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route(
        '/api/admin/quest-templates/{templateId}/conditions/{conditionId}',
        name: 'api_admin_quest_condition_delete',
        methods: ['DELETE'],
        requirements: ['templateId' => '\d+', 'conditionId' => '\d+'],
    )]
    public function delete(int $templateId, int $conditionId): JsonResponse
    {
        $result = $this->questConditionService->deleteCondition($templateId, $conditionId);

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

