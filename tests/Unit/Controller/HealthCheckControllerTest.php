<?php

namespace Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

use function json_decode;

class HealthCheckControllerTest extends TestCase
{
    private HealthCheckController $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new HealthCheckController();
    }

    public function testHealthCheckReturnsOK(): void
    {
        $result = $this->subject->healthCheck();
        self::assertInstanceOf(JsonResponse::class, $result);
        $content = $result->getContent();
        self::assertIsString($content);
        self::assertEquals(['service' => 'OK'], json_decode($content, true));
    }
}
