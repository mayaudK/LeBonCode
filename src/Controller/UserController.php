<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;


class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, loggerInterface $logger, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    #[Route('/register', name: 'user_create', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $user = $this->serializer->deserialize($request->getContent(), User::class, 'json', ['groups' => ['user.create']]);
        } catch (Exception $e) {
            $this->logger->error('Error when deserializing',['errorMessage' => $e->getMessage()]);
            return $this->json([
                'message' => 'Error when deserializing: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);
        $user->setRoles(["ROLE_USER"]);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('User not created',['errorMessage' => $e->getMessage()]);
            return $this->json([
                'message' => 'User not created: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        $this->logger->info('User created', ['user' => $user]);
        return $this->json([
            $user,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['user.create']],
        ]);
    }
}
