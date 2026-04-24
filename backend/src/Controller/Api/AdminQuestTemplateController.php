<?php

namespace App\Controller\Api;

use App\Dto\QuestTemplateDto;
use App\Entity\QuestTemplate;
use App\Repository\QuestTemplateRepository;
use App\Service\QuestTemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminQuestTemplateController extends AbstractController
{
    public function __construct(
        private readonly QuestTemplateService $questTemplateService,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/quest-templates', name: 'api_admin_quest_template_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $templates = $this->questTemplateRepository->findBy([], ['id' => 'DESC']);
        $items = array_map(
            fn (QuestTemplate $template): array => $this->questTemplateService->toArray($template),
            $templates
        );

        return $this->json(['items' => $items]);
    }

    #[Route('/api/admin/quest-templates', name: 'api_admin_quest_template_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, QuestTemplateDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->questTemplateService->createTemplate($dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/quest-templates/{id}', name: 'api_admin_quest_template_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, QuestTemplateDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->questTemplateService->updateTemplate($id, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/quest-templates/{id}/active', name: 'api_admin_quest_template_active', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function setActive(int $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !array_key_exists('isActive', $payload)) {
            return $this->json([
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Champ requis manquant : isActive.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->questTemplateService->setTemplateActive($id, (bool) $payload['isActive']);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/quest-templates/{id}/delete-impact', name: 'api_admin_quest_template_delete_impact', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function deleteImpact(int $id): JsonResponse
    {
        $result = $this->questTemplateService->getDeleteImpact($id);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/quest-templates/{id}', name: 'api_admin_quest_template_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $result = $this->questTemplateService->deleteTemplateAndLinkedEvents($id);

        return $this->json($result, $result['statusCode']);
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T|JsonResponse
     */
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
