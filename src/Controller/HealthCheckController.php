<?php

namespace App\Controller;

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
        $connection = $this->db->getConnection();
        $connection->connect();
        $dbStatus = $connection->isConnected() ? 'Connected' : 'Not connected';

        try {
            $queueStatus = $this->queue->ping() === true ? 'Connected' : 'Not connected';
        } catch (RedisException $e) {
            $queueStatus = 'Not connected';
        }

        return new JsonResponse([
            'queue' => $queueStatus,
            'database' => $dbStatus,
        ], Response::HTTP_OK);
    }
}
