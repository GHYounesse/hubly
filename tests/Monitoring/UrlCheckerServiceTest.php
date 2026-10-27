<?php

namespace App\Tests\Monitoring;

use App\Service\Monitoring\UrlCheckerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class UrlCheckerServiceTest extends TestCase
{
    public function testReachableUrlIsSuccessful(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 200]));

        $result = (new UrlCheckerService($client))->checkUrl('https://example.com');

        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['status_code']);
        $this->assertNull($result['error']);
        $this->assertIsInt($result['response_time_ms']);
        $this->assertGreaterThanOrEqual(0, $result['response_time_ms']);
    }

    public function testErrorStatusCodeIsAFailure(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 500]));

        $result = (new UrlCheckerService($client))->checkUrl('https://example.com');

        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['status_code']);
    }

    public function testSchemeIsAddedWhenMissing(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 200]));

        $result = (new UrlCheckerService($client))->checkUrl('example.com');

        $this->assertSame('http://example.com', $result['url']);
    }

    public function testNetworkErrorIsReportedNotThrown(): void
    {
        $client = new MockHttpClient(static function (): never {
            throw new TransportException('Connection refused');
        });

        $result = (new UrlCheckerService($client))->checkUrl('https://down.example.com');

        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertStringContainsString('Connection refused', $result['error']);
    }
}
