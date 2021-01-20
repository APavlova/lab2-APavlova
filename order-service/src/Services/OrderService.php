<?php

namespace App\Services;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderService
{
    private $entityManager;
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

    public function createOrder($userUUID, string $model, string $size): ?array
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

    public function getUserOrder($userUUID, $orderUUID): ?\App\Entity\Order
    {
        if ($order = $this->orderRepository->findOneBy(['userUUID' => $userUUID, 'orderUUID' => $orderUUID])) {
            return $order;
        } else {
            throw new \Exception('Order with userUUID: '.$userUUID.' and orderUUID: '.$orderUUID.' not found!');
        }
    }

    public function getUserOrders($userUUID): array
    {
        if ($orders = $this->orderRepository->findBy(['userUUID' => $userUUID])) {
            return $orders;
        } else {
            throw new \Exception('Orders with userUUID: '.$userUUID.' not found!');
        }
    }

    public function activateWarranty(Uuid $orderUUID, string $reason): ?array
    {
        if ($order = $this->orderRepository->findOneBy(['orderUUID' => $orderUUID])) {
            $warehouseResponse = $this->client->request(
                'POST',
                getenv('WAREHOUSE_URL').'/warehouse/'.$order->getOrderItemUUID().'/warranty',
                ['json' => ['reason' => $reason]]
            );

            if ($warehouseResponse->getStatusCode() == 200) {
                return $warehouseResponse->toArray();
            } else {
                $reason = $warehouseResponse->toArray(false)['message'];
                throw new \Exception('Can\'t use warranty on orderUUID: '.$orderUUID.'. Reason: '.$reason);
            }
        } else {
            throw new \Exception('Order with orderUUID: '.$orderUUID.' not found!');
        }
    }

    public function returnOrder(Uuid $orderUUID): bool
    {
        if ($order = $this->orderRepository->findOneBy(['orderUUID' => $orderUUID])) {
            $warehouseResponse = $this->client->request(
                'DELETE',
                getenv('WAREHOUSE_URL').'/warehouse/'.$order->getOrderItemUUID()
            );

            if ($warehouseResponse->getStatusCode() == 204) {
                $warrantyResponse = $this->client->request(
                    'DELETE',
                    getenv('WAREHOUSE_URL').'/warehouse/'.$order->getOrderItemUUID()
                );

                if ($warrantyResponse->getStatusCode() == 204) {
                    return true;
                } else {
                    $reason = $warrantyResponse->toArray(false)['message'];
                    throw new \Exception('Can\'t stop warranty on orderItemUUID: '.$orderUUID.'. Reason: '.$reason);
                }
            } else {
                $reason = $warehouseResponse->toArray(false)['message'];
                throw new \Exception('Can\'t return item to warehouse on orderItemUUID: '.$orderUUID.'. Reason: '.$reason);
            }
        } else {
            throw new \Exception('Order with orderUUID: '.$orderUUID.' not found!');
        }
    }
}