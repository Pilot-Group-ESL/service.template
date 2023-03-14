<?php

namespace App\Controller\API\V1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function sprintf;

class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $db,
        private readonly Redis $queue,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api/v1/health', name: 'get_health', methods: ['GET'])]
    public function healthCheck(): Response
    {
        $dbStatus = 'Not connected';
        $redisStatus = 'Not connected';

        $connection = $this->db->getConnection();
        if ($connection instanceof Connection) {
            try {
                $connection->connect();
                $dbStatus = $connection->isConnected() ? 'Connected' : 'Not connected';
            } catch (Exception $e) {
                $this->logger->critical(
                    sprintf('Can not connect to database: %s', $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }

        try {
            $redisStatus = $this->queue->isConnected() ? 'Connected' : 'Not connected';
        } catch (RedisException $e) {
            $this->logger->critical(
                sprintf('Can not connect to Redis: %s', $e->getMessage()),
                ['exception' => $e],
            );
        }

        return new JsonResponse([
            'redis' => $redisStatus,
            'database' => $dbStatus,
        ], Response::HTTP_OK);
    }
}
