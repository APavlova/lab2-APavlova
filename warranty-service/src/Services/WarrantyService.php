<?php

namespace App\Services;

use App\Entity\Warranty;
use App\Repository\WarrantyRepository;
use Doctrine\ORM\EntityManagerInterface;

class WarrantyService
{
    private EntityManagerInterface $entityManager;
    private WarrantyRepository $warrantyRepository;

    public function __construct(EntityManagerInterface $entityManager,
                                WarrantyRepository $warrantyRepository)
    {
        $this->entityManager = $entityManager;
        $this->warrantyRepository = $warrantyRepository;
    }

    public function createWarranty($uuid)
    {
        $warranty = new Warranty();
        $warranty->setComment('This is comment on warranty with uuid = '.$uuid);
        $warranty->setUuid($uuid);
        $warranty->setStatus(Warranty::STATUS_ACTIVE);
        $warranty->setDateStarted(new \DateTime("now"));

        $this->entityManager->persist($warranty);
        $this->entityManager->flush();
    }

    public function deleteWarranty($uuid): bool
    {
        if ($warranty = $this->warrantyRepository->findOneBy(['uuid' => $uuid])) {
            $warranty->setStatus(Warranty::STATUS_REMOVED);
            $warranty->setComment('Warranty was REMOVED!');
            $this->entityManager->flush();
            return true;
        } else {
            return false;
        }
    }

    public function getWarrantyJson($uuid): ?array
    {
        if ($warranty = $this->warrantyRepository->findOneBy(['uuid' => $uuid])) {
            return [
                'itemUUID' => $warranty->getUuid(),
                'comment' => $warranty->getComment(),
                'warrantyDate' => $warranty->getDateStarted()->format('c'),
                'status' => $warranty->getStatus()
            ];
        } else {
            return null;
        }
    }

    public function getDecisionJson($orderItemUUID, $reason, $availableCount): ?array
    {
        if ($warranty = $this->warrantyRepository->findOneBy(['uuid' => $orderItemUUID])) {
            $status = $warranty->getStatus();
            $date = $warranty->getDateStarted()->format('c');

            if ($status == Warranty::STATUS_ACTIVE) {
                $warranty->setStatus(Warranty::STATUS_USED);
                $warranty->setComment('Warranty was USED with reason: '.$reason);
                $this->entityManager->flush();

                if ($availableCount > 0) {
                    return [
                        'warrantyDate' => $date,
                        'decision' => 'RETURN'
                    ];
                } else {
                    return [
                        'warrantyDate' => $date,
                        'decision' => 'FIXING'
                    ];
                }
            } else {
                return [
                    'warrantyDate' => $date,
                    'decision' => 'REFUSED'
                ];
            }
        } else {
            return null;
        }
    }
}