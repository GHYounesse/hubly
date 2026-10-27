<?php

namespace App\Tests\Monitoring;

use App\Entity\Projet;
use App\Entity\UptimeCheck;
use App\Service\Monitoring\UptimeRecorder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UptimeRecorderTest extends TestCase
{
    public function testSuccessfulCheckIsStoredWithItsTiming(): void
    {
        $persisted = null;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted = $entity;
        });
        $em->expects($this->never())->method('flush');

        $projet = new Projet();
        $check = (new UptimeRecorder($em))->record($projet, [
            'success' => true,
            'status_code' => 200,
            'error' => null,
            'response_time_ms' => 230,
        ]);

        $this->assertSame($persisted, $check);
        $this->assertInstanceOf(UptimeCheck::class, $check);
        $this->assertSame($projet, $check->getProjet());
        $this->assertTrue($check->isSuccess());
        $this->assertSame(200, $check->getStatusCode());
        $this->assertSame(230, $check->getResponseTimeMs());
        $this->assertNull($check->getError());
    }

    public function testFailedCheckKeepsTheErrorTruncatedToTheColumnSize(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $check = (new UptimeRecorder($em))->record(new Projet(), [
            'success' => false,
            'status_code' => null,
            'error' => str_repeat('x', 400),
        ]);

        $this->assertFalse($check->isSuccess());
        $this->assertNull($check->getStatusCode());
        $this->assertNull($check->getResponseTimeMs());
        $this->assertSame(255, strlen((string) $check->getError()));
    }
}
