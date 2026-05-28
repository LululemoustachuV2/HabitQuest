<?php

namespace App\Controller\Api;

use App\Dto\MonsterTemplateDto;
use App\Entity\MonsterTemplate;
use App\Repository\MonsterTemplateRepository;
use App\Service\MonsterTemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminMonsterController extends AbstractController
{
    public function __construct(
        private readonly MonsterTemplateService $monsterTemplateService,
        private readonly MonsterTemplateRepository $monsterTemplateRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/admin/monsters', name: 'api_admin_monster_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $monsters = $this->monsterTemplateRepository->findBy([], ['id' => 'DESC']);
        $items = array_map(
            fn (MonsterTemplate $template): array => $this->monsterTemplateService->toArray($template),
            $monsters
        );

        return $this->json(['monsters' => $items]);
    }

    #[Route('/api/admin/monsters/{id}', name: 'api_admin_monster_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $result = $this->monsterTemplateService->getTemplate($id);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/monsters', name: 'api_admin_monster_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, MonsterTemplateDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->monsterTemplateService->createTemplate($dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/monsters/{id}', name: 'api_admin_monster_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, MonsterTemplateDto::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $result = $this->monsterTemplateService->updateTemplate($id, $dto);

        return $this->json($result, $result['statusCode']);
    }

    #[Route('/api/admin/monsters/{id}', name: 'api_admin_monster_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $result = $this->monsterTemplateService->deleteTemplate($id);

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

