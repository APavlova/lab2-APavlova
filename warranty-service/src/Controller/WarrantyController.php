<?php

namespace App\Controller;

use App\Services\WarrantyService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WarrantyController extends AbstractController
{
    /**
     * @Route("/warranty/{itemUUID}", name="warranty_getStatus", methods={"GET"})
     */
    public function getWarrantyStatus($itemUUID): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/WarrantyController.php',
        ]);
    }

    /**
     * @Route("/warranty/{itemUUID}", name="warranty_start", methods={"POST"})
     * @param $itemUUID
     * @param WarrantyService $warrantyService
     * @return Response
     */
    public function startWarranty($itemUUID, WarrantyService $warrantyService): Response
    {
        $warrantyService->createWarranty($itemUUID);

        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/healthCheck", name="warranty_healthCheck", methods={"GET"})
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function healthCheck(EntityManagerInterface $em): Response
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
