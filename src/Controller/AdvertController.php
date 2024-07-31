<?php

namespace App\Controller;

use App\Entity\Advert;
use App\Repository\AdvertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api-advert')]
class AdvertController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AdvertRepository $advertRepository;
    private LoggerInterface $logger;
    public function __construct(
        EntityManagerInterface $entityManager,
        AdvertRepository $advertRepository,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->advertRepository = $advertRepository;
        $this->logger = $logger;
    }

    #[Route('/advert', name: 'app_create_advert', methods: ['POST'])]
    public function createAdvert(
        Request $request,
        #[MapRequestPayload(
            serializationContext: ['groups' => ['advert.create']]
        )]
        Advert $advert,
    ): JsonResponse
    {
        try {
            $this->entityManager->persist($advert);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Advert not created',['errorMessage' => $e->getMessage(), 'data' => $request->getContent()]);
            return $this->json([
                'message' => 'Advert not created: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        $this->logger->info('Advert created', ['advert' => $advert]);
        return $this->json([
            $advert,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['advert.create']],
        ]);
    }

    #[Route('/advert/{id}', name: 'delete_advert', methods: ['DELETE'])]
    public function deleteAdvert(int $id): JsonResponse
    {
        try {
            $advert = $this->advertRepository->find($id);
            if (is_null($advert)) {
                $this->logger->error('Advert #'.$id.' not found',['data' => $id]);
                return $this->json([
                    'message' => 'Advert #'.$id.' not found',
                    'errorCode' => Response::HTTP_NOT_FOUND
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error('Advert not deleted',['errorMessage' => $e->getMessage(), 'data' => $advert ?? $id]);
            return $this->json([
                'message' => $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        $advert->setOnline(false);
        $this->entityManager->persist($advert);
        $this->entityManager->flush();

        $this->logger->info('Advert deleted', ['advert' => $id]);

        return $this->json([
            'message' => 'Advert deleted',
            Response::HTTP_OK
        ]);
    }

    #[Route('/advert/{id}', name: 'patch_advert', methods: ['PATCH'])]
    public function patchAdvert(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Advert $advert,
        Request $request
    ): JsonResponse
    {
        dd($advert);
        $advert = $serializer->deserialize($request->getContent(), Advert::class, 'json', [
            'object_to_populate' => $advert,
            'groups' => ['advert.update'],
        ]);

        $advert->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($advert);
        $entityManager->flush();

        return $this->json([
            $advert,
            Response::HTTP_OK,
            [],
            ['groups' => ['advert.update']],
        ]);
    }

    #[Route('/advert', name: 'get_adverts', methods: ['GET'])]
    public function getAdverts(): JsonResponse
    {
        // Pagination obligatoire
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AdvertController.php',
        ]);
    }

    #[Route('/advert/{id}', name: 'get_advert_by_id', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function getAdvert(Advert $advert): JsonResponse
    {
        if ($advert->getOnline() === false) {
            return $this->json([
                'message' => 'Advert not found',
                Response::HTTP_NOT_FOUND
            ]);
        }
        return $this->json([
            $advert,
            Response::HTTP_OK
        ]);
    }

    #[Route('/advert/search', name: 'search_advert', methods: ['GET'])]
    public function searchAdvert(AdvertResearchDTO $advertResearchDTO): JsonResponse
    {
        $search = $request->query->get('search');
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AdvertController.php',
        ]);
    }
}
