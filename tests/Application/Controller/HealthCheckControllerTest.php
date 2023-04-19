<?php

namespace Tests\Application\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function json_decode;

class HealthCheckControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testHealthCheckReturnsOK(): void
    {
        $this->client->request('GET', '/api/v1/health');

        $response = $this->client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);

        self::assertEquals([
            'status' => 'OK',
            'services' => [
                'redis' => 'Connected',
                'database' => 'Connected',
            ],
        ], json_decode($content, true));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->client);
    }
}
