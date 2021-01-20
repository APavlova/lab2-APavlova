<?php

namespace App\Controller;

use App\Services\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/orders/{userUUID}", name="order_createOrder", methods={"POST"})
     * @param $userUUID
     * @param Request $request
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function takeItem($userUUID, Request $request, OrderService $orderService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $orderJson = $orderService->makeOrder($userUUID, $data['model'], $data['size']);
            return $this->json($orderJson, JsonResponse::HTTP_OK);
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
