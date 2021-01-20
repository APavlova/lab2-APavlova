<?php

namespace App\Controller;

use App\Services\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WarehouseController extends AbstractController
{
    /**
     * @Route("/warehouse", name="warehouse_takeItem", methods={"POST"})
     * @param Request $request
     * @param WarehouseService $warehouseService
     * @return JsonResponse
     * @throws Exception
     */
    public function takeItem(Request $request, WarehouseService $warehouseService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $orderItemJson = $warehouseService->placeNewOrder($data['orderUUID'], $data['model'], $data['size']);
            return $this->json($orderItemJson, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/warehouse/{orderItemUUID}", name="warehouse_getItemOrderInfo", methods={"GET"})
     * @param $orderItemUUID
     * @param WarehouseService $warehouseService
     * @return JsonResponse
     */
    public function getItemOrderInfo($orderItemUUID, WarehouseService $warehouseService): JsonResponse
    {
        if ($itemOrderJson = $warehouseService->getItemOrderInfo($orderItemUUID)) {
            return $this->json($itemOrderJson, JsonResponse::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'OrderItem information for orderItemUUID: '.$orderItemUUID.' not found!'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/warehouse/{orderItemUUID}/warranty", name="warehouse_warrantyRequest", methods={"POST"})
     * @param $orderItemUUID
     * @param Request $request
     * @param WarehouseService $warehouseService
     * @return JsonResponse
     */
    public function warrantyRequest($orderItemUUID, Request $request, WarehouseService $warehouseService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($warrantyJson = $warehouseService->warrantyRequest($orderItemUUID, $data['reason'])) {
            return $this->json($warrantyJson, JsonResponse::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'WarehouseService: '.'Warranty not found for orderItemUUID: '.$orderItemUUID
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/warehouse/{orderItemUUID}", name="warehouse_returnItem", methods={"DELETE"})
     * @param $orderItemUUID
     * @param WarehouseService $warehouseService
     * @return JsonResponse
     */
    public function returnItem($orderItemUUID, WarehouseService $warehouseService): JsonResponse
    {
        if ($warehouseService->returnItem($orderItemUUID)) {
            return $this->json([], JsonResponse::HTTP_NO_CONTENT);
        } else {
            return $this->json([
                'message' => 'OrderItem for orderItemUUID: '.$orderItemUUID.' not found!'
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
