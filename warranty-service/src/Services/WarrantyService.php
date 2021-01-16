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

    public function getWarrantyJson($uuid): ?array
    {
        $warranty = $this->warrantyRepository->findOneBy(['uuid' => $uuid]);


    }
}