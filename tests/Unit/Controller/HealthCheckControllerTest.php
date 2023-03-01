<?php

namespace Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use Symfony\Component\HttpFoundation\JsonResponse;

use function json_decode;
use function sprintf;

class HealthCheckControllerTest extends MockeryTestCase
{
    private HealthCheckController $subject;

    private MockInterface&ManagerRegistry $db;

    private MockInterface&Connection $connection;

    private MockInterface&Redis $redis;

    private MockInterface&LoggerInterface $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->redis = Mockery::mock(Redis::class);
        $this->db = Mockery::mock(ManagerRegistry::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->subject = new HealthCheckController(
            $this->db,
            $this->redis,
            $this->logger,
        );
        $this->connection = Mockery::mock(Connection::class);
    }

    public function testHealthCheckReturnsConnectedWhenBothDBAndQueueAreAvailable(): void
    {
        $this->db->expects()->getConnection()->andReturn($this->connection);
        $this->redis->expects()->isConnected()->andReturnTrue();
        $this->connection->expects()->connect()->andReturnTrue();
        $this->connection->expects()->isConnected()->andReturnTrue();
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals([
            'redis' => 'Connected',
            'database' => 'Connected',
        ], json_decode($content, true));
    }

    public function testHealthCheckReturnsNotConnectedIfConnectionIsSpecifiedIncorrectly(): void
    {
        $this->db->expects()->getConnection()->andReturn(false);
        $this->redis->expects()->isConnected()->andReturnTrue();
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals([
            'redis' => 'Connected',
            'database' => 'Not connected',
        ], json_decode($content, true));
    }

    public function testHealthCheckReturnsNotConnectedIfDatabaseConnectThrowsException(): void
    {
        $this->db->expects()->getConnection()->andReturn($this->connection);
        $this->redis->expects()->isConnected()->andReturnTrue();
        $databaseException = new Exception('SomeException');
        $this->connection->expects()->connect()->andThrow($databaseException);
        $this->logger->expects()->critical(
            sprintf('Can not connect to database: %s', $databaseException->getMessage()),
            ['exception' => $databaseException],
        );
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals([
            'redis' => 'Connected',
            'database' => 'Not connected',
        ], json_decode($content, true));
    }

    public function testHealthCheckReturnsNotConnectedIfIsConnectedThrowsException(): void
    {
        $this->db->expects()->getConnection()->andReturn($this->connection);
        $redisException = new RedisException('SomeException');
        $this->redis->expects()->isConnected()->andThrow($redisException);
        $this->logger->expects()->critical(
            sprintf('Can not connect to Redis: %s', $redisException->getMessage()),
            ['exception' => $redisException],
        );
        $databaseException = new Exception('SomeException');
        $this->connection->expects()->connect()->andReturnTrue();
        $this->connection->expects()->isConnected()->andThrow($databaseException);
        $this->logger->expects()->critical(
            sprintf('Can not connect to database: %s', $databaseException->getMessage()),
            ['exception' => $databaseException],
        );
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals([
            'redis' => 'Not connected',
            'database' => 'Not connected',
        ], json_decode($content, true));
    }

    public function testHealthCheckReturnsNotConnectedIfBothConnectionsReturnFalse(): void
    {
        $this->db->expects()->getConnection()->andReturn($this->connection);
        $this->redis->expects()->isConnected()->andReturnFalse();
        $this->connection->expects()->connect()->andReturnTrue();
        $this->connection->expects()->isConnected()->andReturnFalse();
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals([
            'redis' => 'Not connected',
            'database' => 'Not connected',
        ], json_decode($content, true));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->subject,
            $this->connection,
            $this->db,
            $this->redis,
            $this->logger,
        );
    }
}
