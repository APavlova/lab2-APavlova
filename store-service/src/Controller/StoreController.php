<?php

namespace App\Controller;

use App\Services\StoreService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class StoreController extends AbstractController
{
    /**
     * @Route("/store/{userUUID}/purchase", name="store_makePurchase", methods={"POST"})
     * @param $userUUID
     * @param Request $request
     * @param StoreService $storeService
     * @return JsonResponse
     */
    public function makePurchase($userUUID, Request $request, StoreService $storeService): JsonResponse
    {
        $userUUID = Uuid::fromString($userUUID);
        $data = json_decode($request->getContent(), true);

        try {
            $orderUUID = $storeService->makePurchase($userUUID, $data['model'], $data['size']);
            return $this->json([], JsonResponse::HTTP_CREATED, ['Location' => $request->getUri().'/'.$orderUUID]);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/store/{userUUID}/{orderUUID}", name="store_getUserOrderInfo", methods={"GET"})
     * @param $userUUID
     * @param $orderUUID
     * @param StoreService $storeService
     * @return JsonResponse
     */
    public function getUserOrderInfo($userUUID, $orderUUID, StoreService $storeService): JsonResponse
    {
        $userUUID = Uuid::fromString($userUUID);
        $orderUUID = Uuid::fromString($orderUUID);

        try {
            $orderInfo = $storeService->getUserOrderInfo($userUUID, $orderUUID);
            return $this->json($orderInfo, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/store/{userUUID}/orders", name="store_getUserOrdersInfo", methods={"GET"}, priority=1)
     * @param $userUUID
     * @param StoreService $storeService
     * @return JsonResponse
     */
    public function getUserOrdersInfo($userUUID, StoreService $storeService): JsonResponse
    {
        $userUUID = Uuid::fromString($userUUID);

        try {
            $ordersInfo = $storeService->getUserOrdersInfo($userUUID);
            return $this->json($ordersInfo, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/store/{userUUID}/{orderUUID}/warranty", name="store_activateWarranty", methods={"POST"})
     * @param $userUUID
     * @param $orderUUID
     * @param Request $request
     * @param StoreService $storeService
     * @return JsonResponse
     */
    public function activateWarranty($userUUID, $orderUUID, Request $request, StoreService $storeService): JsonResponse
    {
        $userUUID = Uuid::fromString($userUUID);
        $orderUUID = Uuid::fromString($orderUUID);
        $data = json_decode($request->getContent(), true);

        try {
            $warrantyJson = $storeService->activateWarranty($userUUID, $orderUUID, $data['reason']);
            return $this->json($warrantyJson, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/store/{userUUID}/{orderUUID}/refund", name="store_refundOrder", methods={"DELETE"})
     * @param $userUUID
     * @param $orderUUID
     * @param StoreService $storeService
     * @return JsonResponse
     */
    public function refundOrder($userUUID, $orderUUID, StoreService $storeService): JsonResponse
    {
        $userUUID = Uuid::fromString($userUUID);
        $orderUUID = Uuid::fromString($orderUUID);

        try {
            $storeService->refundOrder($userUUID, $orderUUID);
            return $this->json([], JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/healthCheck", name="order_healthCheck", methods={"GET"})
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
