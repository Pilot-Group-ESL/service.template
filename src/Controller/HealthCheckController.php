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

use function assert;
use function dd;

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
        assert($connection instanceof Connection);
        var_dump($connection->getDatabase());
        try {
            $connection->connect();
            $dbStatus = $connection->isConnected() ? 'Connected' : 'Not connected';
        } catch (Exception $e) {
            $dbStatus = 'Not connected';
        }

        try {
            $queueStatus = $this->queue->isConnected() ? 'Connected' : 'Not connected';
        } catch (RedisException $e) {
            $queueStatus = 'Not connected';
        }

        return new JsonResponse([
            'queue' => $queueStatus,
            'database' => $dbStatus,
        ], Response::HTTP_OK);
    }
}
