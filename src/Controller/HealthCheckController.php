<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Redis;
use RedisException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $db,
        private readonly Redis $queue,
    ) {
    }

    #[Route('/health', name: 'get_heath', methods: ['GET'])]
    public function healthCheck(): Response
    {
        $dbStatus = 'Not connected';
        $queueStatus = 'Not connected';

        $connection = $this->db->getConnection();
        if ($connection instanceof Connection) {
            try {
                $connection->connect();
                $dbStatus = $connection->isConnected() ? 'Connected' : 'Not connected';
            } catch (Exception $e) {
                //TODO: Add logging here.
            }
        }

        try {
            $queueStatus = $this->queue->isConnected() ? 'Connected' : 'Not connected';
        } catch (RedisException $e) {
            //TODO: Add logging here.
        }

        return new JsonResponse([
            'queue' => $queueStatus,
            'database' => $dbStatus,
        ], Response::HTTP_OK);
    }
}
