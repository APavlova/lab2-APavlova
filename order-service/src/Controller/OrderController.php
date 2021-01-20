<?php

namespace App\Controller;

use App\Services\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class OrderController extends AbstractController
{
    /**
     * @Route("/orders/{userUUID}", name="order_createOrder", methods={"POST"})
     * @param $userUUID
     * @param Request $request
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function createOrder($userUUID, Request $request, OrderService $orderService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $orderJson = $orderService->createOrder($userUUID, $data['model'], $data['size']);
            return $this->json($orderJson, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/orders/{userUUID}/{orderUUID}", name="order_getUserOrder", methods={"GET"})
     * @param $userUUID
     * @param $orderUUID
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function getUserOrder($userUUID, $orderUUID, OrderService $orderService): JsonResponse
    {
        try {
            $order = $orderService->getUserOrder($userUUID, $orderUUID);
            return $this->json([
                'orderUUID' => $order->getOrderUUID(),
                'orderDate' => $order->getOrderDate(),
                'orderItemUUID' => $order->getOrderItemUUID(),
                'status' => $order->getStatus()
            ], JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/orders/{userUUID}", name="order_getUserOrders", methods={"GET"})
     * @param $userUUID
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function getUserOrders($userUUID, OrderService $orderService): JsonResponse
    {
        try {
            $orders = $orderService->getUserOrders($userUUID);
            $ordersArray = array();

            foreach ($orders as $order) {
                $ordersArray[] = [
                    'orderUUID' => $order->getOrderUUID(),
                    'orderDate' => $order->getOrderDate(),
                    'orderItemUUID' => $order->getOrderItemUUID(),
                    'status' => $order->getStatus()
                ];
            }

            return $this->json($ordersArray, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/orders/{orderUUID}/warranty", name="order_activateWarranty", methods={"POST"})
     * @param $orderUUID
     * @param Request $request
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function activateWarranty($orderUUID, Request $request, OrderService $orderService): JsonResponse
    {
        $orderUUID = Uuid::fromString($orderUUID);
        $data = json_decode($request->getContent(), true);

        try {
            $warrantyJson = $orderService->activateWarranty($orderUUID, $data['reason']);
            return $this->json($warrantyJson, JsonResponse::HTTP_OK);
        } catch (Exception $exception) {
            return $this->json([
                'message' => 'OrderService: '.$exception->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/orders/{orderUUID}", name="order_returnOrder", methods={"DELETE"})
     * @param $orderUUID
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function returnOrder($orderUUID, OrderService $orderService): JsonResponse
    {
        $orderUUID = Uuid::fromString($orderUUID);

        try {
            $orderService->returnOrder($orderUUID);
            return $this->json([], JsonResponse::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            return $this->json([
                'message' => 'OrderService: '.$exception->getMessage()
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
