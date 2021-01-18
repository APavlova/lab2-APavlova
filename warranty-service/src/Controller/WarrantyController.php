<?php

namespace App\Controller;

use App\Services\WarrantyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WarrantyController extends AbstractController
{
    /**
     * @Route("/warranty/{itemUUID}", name="warranty_getStatus", methods={"GET"})
     * @param $itemUUID
     * @param WarrantyService $warrantyService
     * @return JsonResponse
     */
    public function getWarrantyStatus($itemUUID, WarrantyService $warrantyService): JsonResponse
    {
        if ($warrantyJson = $warrantyService->getWarrantyJson($itemUUID)) {
            return $this->json($warrantyJson, JsonResponse::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'Warranty for itemUUID: '.$itemUUID.' not found!'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/warranty/{itemUUID}", name="warranty_start", methods={"POST"})
     * @param $itemUUID
     * @param WarrantyService $warrantyService
     * @return JsonResponse
     */
    public function startWarranty($itemUUID, WarrantyService $warrantyService): JsonResponse
    {
        $warrantyService->createWarranty($itemUUID);

        return $this->json([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/warranty/{itemUUID}/warranty", name="warranty_request", methods={"POST"})
     * @param $itemUUID
     * @param WarrantyService $warrantyService
     * @param Request $request
     * @return JsonResponse
     */
    public function requestWarranty($itemUUID, WarrantyService $warrantyService, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($decisionJson = $warrantyService->getDecisionJson($itemUUID, $data['reason'], $data['availableCount'])) {
            return $this->json($decisionJson, JsonResponse::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'Warranty for itemUUID: '.$itemUUID.' not found!'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/warranty/{itemUUID}", name="warranty_delete", methods={"DELETE"})
     * @param $itemUUID
     * @param WarrantyService $warrantyService
     * @return JsonResponse
     */
    public function deleteWarranty($itemUUID, WarrantyService $warrantyService): JsonResponse
    {
        if ($warrantyService->deleteWarranty($itemUUID)) {
            return $this->json([
                'message' => 'Warranty for itemUUID: '.$itemUUID.' was removed!'
            ], JsonResponse::HTTP_NO_CONTENT);
        } else {
            return $this->json([
                'message' => 'Warranty for itemUUID: '.$itemUUID.' not found!'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/healthCheck", name="warranty_healthCheck", methods={"GET"})
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function healthCheck(EntityManagerInterface $em): JsonResponse
    {
        $connection = $em->getConnection();
        $connection->connect();

        return $this->json([
            'app' => [
                'active' => true
            ],
            'db' => [
                'active' => $connection->isConnected(),
                'driver' => $connection->getParams()['driver'],
                'host' => $connection->getParams()['host'].':'.$connection->getParams()['port'],
                'db_name' => $connection->getParams()['dbname']
            ]
        ]);
    }
}
