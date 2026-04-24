<?php

namespace App\Controller\Api;

use App\Dto\RegisterUserDto;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            /** @var RegisterUserDto $dto */
            $dto = $this->serializer->deserialize($request->getContent(), RegisterUserDto::class, 'json');
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

        if ($this->userRepository->findOneByEmail($dto->email) instanceof User) {
            return $this->json(['message' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        $user = (new User())
            ->setEmail($dto->email)
            ->setRole(UserRole::USER);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'Compte créé.'], Response::HTTP_CREATED);
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Route interceptée par le firewall json_login. Cette réponse ne devrait
        // pas être atteinte lorsque l'authentification réussit ou échoue.
        return $this->json(['message' => 'Point d\'entrée de connexion actif.'], Response::HTTP_OK);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Côté serveur l'API est stateless (JWT) : il n'y a rien à invalider ici.
        // Le client doit simplement supprimer le token de son stockage local.
        return $this->json(['message' => 'Déconnexion effectuée. Supprimez le token côté client.'], Response::HTTP_OK);
    }
}
