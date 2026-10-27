<?php

namespace App\Tests\Api;

use App\EventSubscriber\ApiRateLimitSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ApiRateLimitSubscriberTest extends TestCase
{
    private function subscriber(int $limit = 2): ApiRateLimitSubscriber
    {
        $factory = new RateLimiterFactory(
            ['id' => 'api', 'policy' => 'fixed_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );

        return new ApiRateLimitSubscriber($factory, new TokenStorage());
    }

    private function requestEvent(string $path, string $ip = '203.0.113.7'): RequestEvent
    {
        $request = Request::create($path, 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testRequestsOverTheLimitAreRejectedWithRetryAfter(): void
    {
        $subscriber = $this->subscriber(2);

        $subscriber->onRequest($this->requestEvent('/api/projects'));
        $subscriber->onRequest($this->requestEvent('/api/projects'));

        try {
            $subscriber->onRequest($this->requestEvent('/api/projects'));
            $this->fail('The third request should be rejected');
        } catch (TooManyRequestsHttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertArrayHasKey('Retry-After', $e->getHeaders());
            $this->assertGreaterThanOrEqual(1, (int) $e->getHeaders()['Retry-After']);
        }
    }

    public function testEachClientHasItsOwnQuota(): void
    {
        $subscriber = $this->subscriber(1);

        $subscriber->onRequest($this->requestEvent('/api/projects', '203.0.113.7'));
        $subscriber->onRequest($this->requestEvent('/api/projects', '203.0.113.8'));

        $this->addToAssertionCount(1); // no exception: two different IPs, one request each
    }

    public function testPagesOutsideTheApiAreNotLimited(): void
    {
        $subscriber = $this->subscriber(1);

        for ($i = 0; $i < 5; ++$i) {
            $subscriber->onRequest($this->requestEvent('/dashboard'));
        }

        $this->addToAssertionCount(1);
    }

    public function testResponsesCarryTheRemainingQuota(): void
    {
        $subscriber = $this->subscriber(5);
        $requestEvent = $this->requestEvent('/api/projects');
        $subscriber->onRequest($requestEvent);

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $requestEvent->getRequest(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );
        $subscriber->onResponse($responseEvent);

        $headers = $responseEvent->getResponse()->headers;
        $this->assertSame('5', $headers->get('X-RateLimit-Limit'));
        $this->assertSame('4', $headers->get('X-RateLimit-Remaining'));
    }
}
