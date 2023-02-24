<?php

namespace Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Redis;
use Symfony\Component\HttpFoundation\JsonResponse;

use function json_decode;

class HealthCheckControllerTest extends MockeryTestCase
{
    private HealthCheckController $subject;
    private MockInterface&ManagerRegistry $db;
    private MockInterface&Connection $connection;
    private MockInterface&Redis $redis;

    public function setUp(): void
    {
        parent::setUp();
        $this->redis = Mockery::mock(Redis::class);
        $this->db = Mockery::mock(ManagerRegistry::class);
        $this->subject = new HealthCheckController(
            $this->db,
            $this->redis,
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
            'queue' => 'Connected',
            'database' => 'Connected',
        ], json_decode($content, true));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->subject,
            $this->db,
            $this->redis,
            $this->connection,
        );
    }
}
