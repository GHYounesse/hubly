<?php

namespace App\Tests\Monitoring;

use App\Entity\Notification;
use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Service\Monitoring\UptimeRecorder;
use App\Service\Monitoring\UrlCheckerService;
use App\Service\Monitoring\UrlService;
use App\Service\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class UrlServiceTest extends TestCase
{
    public function testEveryCheckIsRecordedButOnlyFailuresRaiseAlerts(): void
    {
        $healthy = (new Projet())->setNom('Healthy')->setUrl('https://healthy.example');
        $broken = (new Projet())->setNom('Broken')->setUrl('https://broken.example');
        $noUrl = (new Projet())->setNom('No URL');

        $projetRepository = $this->createMock(ProjetRepository::class);
        $projetRepository->method('findAll')->willReturn([$healthy, $broken, $noUrl]);

        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 200]),
            new MockResponse('', ['http_code' => 503]),
        ]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($this->createMock(EntityRepository::class));
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $em->expects($this->once())->method('flush');

        $notifier = $this->createMock(NotificationService::class);
        $notifier->expects($this->once())->method('sendNotification')
            ->with($this->anything(), $this->stringContains('Broken'), 'alerts@example.com');

        $service = new UrlService(
            $projetRepository,
            new UrlCheckerService($client),
            new UptimeRecorder($em),
            $em,
            $notifier,
            'alerts@example.com',
        );

        $errors = $service->checkUrls();

        $this->assertSame(['https://broken.example'], $errors);

        $checks = array_filter($persisted, static fn (object $e): bool => $e instanceof \App\Entity\UptimeCheck);
        $notifications = array_filter($persisted, static fn (object $e): bool => $e instanceof Notification);

        $this->assertCount(2, $checks, 'a project without URL is skipped, the two others are recorded');
        $this->assertCount(1, $notifications);
    }
}
