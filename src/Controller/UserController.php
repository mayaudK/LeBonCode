<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/register', name: 'user_create', methods: ['POST'])]
    public function createUser(
        #[MapRequestPayload(
            serializationContext: ['groups' => ['user.create']]
        )]
        User $user,
    ): JsonResponse
    {

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            $user,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['user.create']],
        ]);
    }
}
