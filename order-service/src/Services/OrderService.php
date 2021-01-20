<?php

namespace App\Services;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderService
{
    private $entityManager;
    private $itemRepository;
    private $orderItemRepository;
    private $orderRepository;
    private $client;

    public function __construct(EntityManagerInterface $entityManager,
                                OrderRepository $orderRepository,
                                HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->client = $client;
    }

    public function makeOrder($userUUID, string $model, string $size)
    {
        $orderUUID = Uuid::v4();

        $warehouseResponse = $this->client->request(
            'POST',
            getenv('WAREHOUSE_URL').'/warehouse',
            [
                'json' => [
                    'orderUUID' => $orderUUID,
                    'model' => $model,
                    'size' => $size
                ],
            ]);

        if ($warehouseResponse->getStatusCode() == 200) {
            $orderItemUUID = $warehouseResponse->toArray()['orderItemUUID'];

            $warrantyResponse = $this->client->request(
                'POST',
                getenv('WARRANTY_URL').'/warranty/'.$orderItemUUID
            );

            if ($warrantyResponse->getStatusCode() == 204) {
                $order = new Order();
                $order->setOrderItemUUID($orderItemUUID);
                $order->setOrderDate(new \DateTime('now'));
                $order->setStatus(Order::STATUS_PAID);
                $order->setOrderUUID($orderUUID);
                $order->setUserUUID($userUUID);

                $this->entityManager->persist($order);
                $this->entityManager->flush();

                return [
                    'orderUUID' => $orderUUID
                ];
            } else {
                throw new \Exception('Can\'t create warranty for orderItemUUID: '.$orderItemUUID);
            }
        } else {
            throw new \Exception('Can\'t take item from warehouse with orderUUID: '.$orderUUID);
        }
    }
}