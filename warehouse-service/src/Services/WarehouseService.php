<?php

namespace App\Services;

use App\Entity\Item;
use App\Entity\OrderItem;
use App\Repository\ItemRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WarehouseService
{
    private $entityManager;
    private $itemRepository;
    private $orderItemRepository;
    private $client;

    public function __construct(EntityManagerInterface $entityManager,
                                ItemRepository $itemRepository,
                                OrderItemRepository $orderItemRepository,
                                HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->itemRepository = $itemRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->client = $client;
    }

    public function placeNewOrder($orderUUID, string $model, string $size): array
    {
        if (!$item = $this->itemRepository->findOneBy(['model' => $model, 'size' => $size]))
            throw new \Exception('Item with model = '.$model.' and size = '.$size.' not found!');

        $availableCount = $item->getAvailableCount();

        if ($availableCount < 1)
            throw new \Exception('Item '.$model.' is finished in warehouse!');

        $orderItem = new OrderItem();
        $orderItem->setIsCanceled(false);
        $orderItem->setOrderItemUUID(Uuid::v4());
        $orderItem->setOrderUUID($orderUUID);
        $orderItem->setItem($item);

        $item->setAvailableCount($availableCount - 1);

        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return [
            'orderItemUUID' => $orderItem->getOrderItemUUID(),
            'orderUUID' => $orderItem->getOrderUUID(),
            'model' => $orderItem->getItem()->getModel(),
            'size' => $orderItem->getItem()->getSize()
        ];
    }

    public function getItemOrderInfo($orderItemUUID): ?array
    {
        if ($orderItem = $this->orderItemRepository->findOneBy(['orderItemUUID' => $orderItemUUID])) {
            return [
                'model' => $orderItem->getItem()->getModel(),
                'size' => $orderItem->getItem()->getSize()
            ];
        } else {
            return null;
        }
    }

    public function warrantyRequest($orderUUID, string $reason): ?array
    {
        $response = $this->client->request(
            'POST',
            getenv('WARRANTY_URL').'/warranty/'.$orderUUID.'/warranty',
            ['json' => ['reason' => $reason],
        ]);

        if ($response->getStatusCode() == 200) {
            return $response->toArray();
        } else {
            return null;
        }
    }

    public function returnItem($orderItemUUID): bool
    {
        if ($orderItem = $this->orderItemRepository->findOneBy(['orderItemUUID' => $orderItemUUID])) {
            $orderItem->getItem()->setAvailableCount($orderItem->getItem()->getAvailableCount() + 1);
            $orderItem->setIsCanceled(true);
            $this->entityManager->flush();
            return true;
        } else {
            return false;
        }
    }
}