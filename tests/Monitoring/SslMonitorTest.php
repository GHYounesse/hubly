<?php

namespace App\Tests\Monitoring;

use App\Entity\Notification;
use App\Entity\Projet;
use App\Repository\ProjetRepository;
use App\Service\Monitoring\SslCertificateChecker;
use App\Service\Monitoring\SslMonitor;
use App\Service\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class SslMonitorTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-11-01 12:00:00');
    }

    public function testHostIsExtractedFromHttpsUrlsOnly(): void
    {
        $checker = new SslCertificateChecker();

        $this->assertSame('shop.example.com', $checker->hostFromUrl('https://Shop.Example.com/path?x=1'));
        $this->assertSame('example.com', $checker->hostFromUrl('example.com'), 'no scheme: https is assumed');
        $this->assertNull($checker->hostFromUrl('http://example.com'), 'plain http has no certificate');
        $this->assertNull($checker->hostFromUrl(''));
    }

    public function testDaysLeftCountsWholeDaysAndGoesNegativeOnceExpired(): void
    {
        $this->assertSame(5, SslMonitor::daysLeft($this->now->modify('+5 days 3 hours'), $this->now));
        $this->assertSame(0, SslMonitor::daysLeft($this->now->modify('+3 hours'), $this->now));
        $this->assertSame(-2, SslMonitor::daysLeft($this->now->modify('-1 day -3 hours'), $this->now));
    }

    public function testACertificateExpiringSoonRaisesOneAlert(): void
    {
        [$monitor, $persisted] = $this->monitorWith(['https://soon.example.test' => $this->now->modify('+5 days')], notifications: 1);

        $warnings = $monitor->checkAll($this->now);

        $this->assertSame(1, $warnings);
        $this->assertCount(1, array_filter($persisted(), static fn ($e) => $e instanceof Notification));
    }

    public function testACertificateWithPlentyOfTimeLeftRaisesNothing(): void
    {
        [$monitor, $persisted, $projet] = $this->monitorWith(['https://fine.example.test' => $this->now->modify('+90 days')], notifications: 0);

        $this->assertSame(0, $monitor->checkAll($this->now));
        $this->assertSame([], $persisted());
        $this->assertEquals($this->now->modify('+90 days'), $projet->getSslExpiresAt());
        $this->assertEquals($this->now, $projet->getSslCheckedAt());
    }

    public function testAnUnreachableServerKeepsTheLastKnownDate(): void
    {
        [$monitor, , $projet] = $this->monitorWith(['https://down.example.test' => null], notifications: 0);
        $known = $this->now->modify('+40 days');
        $projet->setSslExpiresAt($known);

        $this->assertSame(0, $monitor->checkAll($this->now));
        $this->assertSame($known, $projet->getSslExpiresAt());
    }

    /**
     * @param array<string, \DateTimeImmutable|null> $urlToExpiry
     *
     * @return array{0: SslMonitor, 1: \Closure, 2: Projet}
     */
    private function monitorWith(array $urlToExpiry, int $notifications): array
    {
        $url = array_key_first($urlToExpiry);
        $projet = (new Projet())->setNom('Demo')->setUrl($url);

        $repository = $this->createMock(ProjetRepository::class);
        $repository->method('findAll')->willReturn([$projet]);

        $checker = $this->createMock(SslCertificateChecker::class);
        $checker->method('hostFromUrl')->willReturn(parse_url($url, PHP_URL_HOST));
        $checker->method('getExpiry')->willReturn($urlToExpiry[$url]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($this->createMock(EntityRepository::class));
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $notifier = $this->createMock(NotificationService::class);
        $notifier->expects($this->exactly($notifications))->method('sendNotification');

        return [
            new SslMonitor($repository, $checker, $em, $notifier, 'alerts@example.test'),
            static function () use (&$persisted): array {
                return $persisted;
            },
            $projet,
        ];
    }
}
