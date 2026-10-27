<?php

namespace App\Tests\Api;

use App\EventSubscriber\SecurityHeadersSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HealthAndHeadersTest extends WebTestCase
{
    public function testTheHealthEndpointIsPublicAndReportsTheDatabase(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"status":"ok","database":"ok"}', (string) $client->getResponse()->getContent());
    }

    public function testEveryResponseCarriesTheHardeningHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertResponseHeaderSame('X-Frame-Options', 'DENY');
        $this->assertResponseHasHeader('Referrer-Policy');
        $this->assertResponseHasHeader('Permissions-Policy');
    }

    public function testHtmlPagesGetAReportOnlyContentSecurityPolicyInProduction(): void
    {
        $response = $this->respond(new SecurityHeadersSubscriber(false), 'text/html; charset=UTF-8', secure: true);

        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'), 'report-only: it must not block anything yet');
        $this->assertStringContainsString('max-age=', (string) $response->headers->get('Strict-Transport-Security'));
    }

    public function testJsonResponsesAndDebugModeSkipTheCspAndHsts(): void
    {
        $json = $this->respond(new SecurityHeadersSubscriber(false), 'application/json', secure: false);
        $this->assertNull($json->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($json->headers->get('Strict-Transport-Security'), 'HSTS only over https');

        $debug = $this->respond(new SecurityHeadersSubscriber(true), 'text/html', secure: true);
        $this->assertNull($debug->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($debug->headers->get('Strict-Transport-Security'));
    }

    private function respond(SecurityHeadersSubscriber $subscriber, string $contentType, bool $secure): Response
    {
        $request = Request::create('/', 'GET', [], [], [], $secure ? ['HTTPS' => 'on'] : []);
        $response = new Response('', 200, ['Content-Type' => $contentType]);
        $event = new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $subscriber->onResponse($event);

        return $response;
    }
}
