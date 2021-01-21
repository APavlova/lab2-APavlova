<?php

namespace App\Services;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StoreService
{
    private $entityManager;
    private $userRepository;
    private $client;

    public function __construct(EntityManagerInterface $entityManager,
                                UserRepository $userRepository,
                                HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->client = $client;
    }

    public function makePurchase(Uuid $userUUID, string $model, string $size): Uuid
    {
        if (!$this->userRepository->findOneBy(['userUUID' => $userUUID]))
            throw new \Exception('User with userUUID '.$userUUID.' not found!');

        $orderResponse = $this->client->request(
                'POST',
                getenv('ORDER_URL').'/orders/'.$userUUID,
                ['json' => ['model' => $model, 'size' => $size]]
        );

        if ($orderResponse->getStatusCode() == 200) {
            return Uuid::fromString($orderResponse->toArray()['orderUUID']);
        } else {
            $reason = $orderResponse->toArray()['message'];
            throw new \Exception('Purchase can\'t be made! Reason: '.$reason);
        }
    }

    public function getUserOrderInfo(Uuid $userUUID, Uuid $orderUUID): ?array
    {
        if (!$this->userRepository->findOneBy(['userUUID' => $userUUID]))
            throw new \Exception('StoreService: User with userUUID '.$userUUID.' not found!');

        $orderResponse = $this->client->request(
            'GET',
            getenv('ORDER_URL').'/orders/'.$userUUID.'/'.$orderUUID
        );

        if ($orderResponse->getStatusCode() !== 200)
            throw new \Exception($orderResponse->toArray()['message']);

        $orderItemUUID = $orderResponse->toArray()['orderItemUUID'];

        $warehouseResponse = $this->client->request(
            'GET',
            getenv('WAREHOUSE_URL').'/warehouse/'.$orderItemUUID
        );

        if ($warehouseResponse->getStatusCode() !== 200)
            throw new \Exception($warehouseResponse->toArray()['message']);

        $warrantyResponse = $this->client->request(
            'GET',
            getenv('WARRANTY_URL').'/warranty/'.$orderItemUUID
        );

        if ($warrantyResponse->getStatusCode() !== 200)
            throw new \Exception($warrantyResponse->toArray()['message']);

        return [
            'orderUUID' => $orderUUID,
            'orderDate' => $orderResponse->toArray()['orderDate'],
            'model' => $warehouseResponse->toArray()['model'],
            'size' => $warehouseResponse->toArray()['size'],
            'warrantyDate' => $warrantyResponse->toArray()['warrantyDate'],
            'warrantyStatus' => $warrantyResponse->toArray()['status']
        ];
    }

    public function getUserOrdersInfo(Uuid $userUUID): ?array
    {
        if (!$this->userRepository->findOneBy(['userUUID' => $userUUID]))
            throw new \Exception('StoreService: User with userUUID '.$userUUID.' not found!');

        $ordersResponse = $this->client->request(
            'GET',
            getenv('ORDER_URL').'/orders/'.$userUUID
        );

        if ($ordersResponse->getStatusCode() !== 200)
            throw new \Exception($ordersResponse->toArray()['message']);

        $orders = $ordersResponse->toArray();
        $ordersArray = array();

        foreach ($orders as $order) {
            $warehouseResponse = $this->client->request(
                'GET',
                getenv('WAREHOUSE_URL').'/warehouse/'.$order['orderItemUUID']
            );

            $warrantyResponse = $this->client->request(
                'GET',
                getenv('WARRANTY_URL').'/warranty/'.$order['orderItemUUID']
            );

            $ordersArray[] = [
                'orderUUID' => $order['orderUUID'],
                'orderDate' => $order['orderDate'],
                'model' => $warehouseResponse->toArray()['model'],
                'size' => $warehouseResponse->toArray()['size'],
                'warrantyDate' => $warrantyResponse->toArray()['warrantyDate'],
                'warrantyStatus' => $warrantyResponse->toArray()['status']
            ];
        }

        return $ordersArray;
    }

    public function activateWarranty(Uuid $userUUID, Uuid $orderUUID, string $reason): ?array
    {
        if (!$this->userRepository->findOneBy(['userUUID' => $userUUID]))
            throw new \Exception('StoreService: User with userUUID '.$userUUID.' not found!');

        $orderResponse = $this->client->request(
            'POST',
            getenv('ORDER_URL').'/orders/'.$orderUUID.'/warranty',
            ['json' => ['reason' => $reason]]
        );

        if ($orderResponse->getStatusCode() !== 200)
            throw new \Exception($orderResponse->toArray()['message']);

        return [
            'orderUUID' => $orderUUID,
            'warrantyDate' => $orderResponse->toArray()['warrantyDate'],
            'decision' => $orderResponse->toArray()['decision']
        ];
    }

    public function refundOrder(Uuid $userUUID, Uuid $orderUUID)
    {
        if (!$this->userRepository->findOneBy(['userUUID' => $userUUID]))
            throw new \Exception('StoreService: User with userUUID '.$userUUID.' not found!');

        $orderResponse = $this->client->request('DELETE',getenv('ORDER_URL').'/orders/'.$orderUUID);

        if ($orderResponse->getStatusCode() !== 204)
            throw new \Exception($orderResponse->toArray()['message']);
    }
}