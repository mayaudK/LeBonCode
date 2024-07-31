<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, loggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/register', name: 'user_create', methods: ['POST'])]
    public function createUser(
        #[MapRequestPayload(
            serializationContext: ['groups' => ['user.create']]
        )]
        User $user,
    ): JsonResponse
    {

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
