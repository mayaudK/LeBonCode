<?php

namespace App\Controller;

use App\AdvertService;
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
    private AdvertService $advertService;
    public function __construct(
        EntityManagerInterface $entityManager,
        AdvertRepository $advertRepository,
        LoggerInterface $logger,
        AdvertService $advertService
    )
    {
        $this->entityManager = $entityManager;
        $this->advertRepository = $advertRepository;
        $this->logger = $logger;
        $this->advertService = $advertService;
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
            $this->advertService->save($advert, $this->entityManager);
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
        $this->advertService->save($advert, $this->entityManager);

        $this->logger->info('Advert deleted', ['advert' => $id]);

        return $this->json([
            'message' => 'Advert deleted',
            Response::HTTP_OK
        ]);
    }

    #[Route('/advert/{id}', name: 'patch_advert', requirements: ['id' => Requirement::DIGITS] , methods: ['PATCH'])]
    public function patchAdvert(
        SerializerInterface $serializer,
        Request $request,
        int $id
    ): JsonResponse
    {
        $advert = $this->advertRepository->find($id);
        if (is_null($advert)) {
            $this->logger->error('Advert #'.$id.' not found',['data' => $id]);
            return $this->json([
                'message' => 'Advert #'.$id.' not found',
                Response::HTTP_NOT_FOUND
            ]);
        }

        try {
            $advert = $serializer->deserialize($request->getContent(), Advert::class, 'json', [
                'object_to_populate' => $advert,
                'groups' => ['advert.update'],
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error when deserializing #'.$id, ['errorMessage' => $e->getMessage(), 'data' => $id]);
            return $this->json([
                'message' => 'Error when deserializing #'.$id.': ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        $advert->setUpdatedAt(new \DateTimeImmutable());
        $this->advertService->save($advert, $this->entityManager);
        $this->logger->info('Advert updated', ['advert' => $advert]);

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
        // Pagination souhaitée pour optimisation d'api mais pas trop le temps d'implémenter
        try {
            $adverts = $this->advertRepository->findAll();
        } catch (Exception $e) {
            $this->logger->error('Error when retrieving adverts',['errorMessage' => $e->getMessage(), 'method' => __METHOD__]);
            return $this->json([
                'message' => 'Error when retrieving adverts: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        if (empty($adverts)) {
            $this->logger->error('No adverts found', ['method' => __METHOD__]);
            return $this->json([
                'message' => 'No adverts found',
                Response::HTTP_NOT_FOUND
            ]);
        }

        // logs not necessary here
        return $this->json([
            'adverts' => $adverts,
            Response::HTTP_OK
        ]);
    }

    #[Route('/advert/{id}', name: 'get_advert_by_id', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function getAdvert(int $id): JsonResponse
    {
        $advert = $this->advertRepository->find($id);

        if (is_null($advert)) {
            $this->logger->error('Advert #'.$id.' not found',['data' => $id]);
            return $this->json([
                'message' => 'Advert #'.$id.' not found',
                Response::HTTP_NOT_FOUND
            ]);
        }

        return $this->json([
            $advert,
            Response::HTTP_OK
        ]);
    }

    #[Route('/advert/search', name: 'search_advert', methods: ['GET'])]
    public function searchAdvert(Request $request): JsonResponse
    {
        $title = $request->query->get('title');
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');

        try {
            $adverts = $this->advertRepository->search($title, $priceMin, $priceMax);
        } catch (Exception $e) {
            $this->logger->error('Error when searching adverts',['errorMessage' => $e->getMessage(), 'method' => __METHOD__]);
            return $this->json([
                'message' => 'Error when searching adverts: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            ]);
        }

        return $this->json([
            'adverts' => $adverts,
            Response::HTTP_OK
        ]);
    }
}
