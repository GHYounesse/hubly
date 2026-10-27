<?php

namespace App\Tests\Project;

use App\Entity\Projet;
use App\Service\Project\TimelineBuilder;
use PHPUnit\Framework\TestCase;

class TimelineBuilderTest extends TestCase
{
    private \DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new \DateTimeImmutable('2026-06-15');
    }

    private function projet(string $name, ?string $ordered, ?string $delivered, string $status = 'en cours'): Projet
    {
        return (new Projet())
            ->setNom($name)
            ->setStatus($status)
            ->setDateCommande($ordered ? new \DateTime($ordered) : null)
            ->setDateLivraison($delivered ? new \DateTime($delivered) : null);
    }

    public function testProjectsWithoutDeliveryDateAreLeftOut(): void
    {
        $timeline = (new TimelineBuilder())->build([$this->projet('No date', '2026-01-01', null)], $this->today);

        $this->assertSame([], $timeline['rows']);
        $this->assertNull($timeline['start']);
    }

    public function testBarsArePlacedAsPercentagesOfThePeriod(): void
    {
        $timeline = (new TimelineBuilder())->build([
            $this->projet('First half', '2026-01-01', '2026-07-01'),
            $this->projet('Second half', '2026-07-01', '2026-12-31'),
        ], $this->today);

        $this->assertCount(2, $timeline['rows']);
        $first = $timeline['rows'][0];
        $this->assertSame('First half', $first['projet']->getNom());
        $this->assertSame(0.0, $first['left']);
        $this->assertEqualsWithDelta(49.9, $first['left'] + $first['width'], 1.0);
        $this->assertLessThanOrEqual(100.0, $timeline['rows'][1]['left'] + $timeline['rows'][1]['width']);
    }

    public function testALateProjectIsFlaggedUnlessItIsDone(): void
    {
        $timeline = (new TimelineBuilder())->build([
            $this->projet('Late', '2026-01-01', '2026-03-01', 'en cours'),
            $this->projet('Finished late', '2026-01-01', '2026-03-01', 'terminé'),
            $this->projet('On time', '2026-01-01', '2026-09-01', 'en cours'),
        ], $this->today);

        $byName = [];
        foreach ($timeline['rows'] as $row) {
            $byName[$row['projet']->getNom()] = $row;
        }

        $this->assertTrue($byName['Late']['overdue']);
        $this->assertFalse($byName['Finished late']['overdue']);
        $this->assertFalse($byName['On time']['overdue']);
    }

    public function testADeliveryRiskIsFlagged(): void
    {
        $risky = $this->projet('Risky', '2026-01-01', '2026-09-01')->setRisqueLivraison('Client indisponible');
        $timeline = (new TimelineBuilder())->build([$risky, $this->projet('Calm', '2026-01-01', '2026-09-01')], $this->today);

        $flags = [];
        foreach ($timeline['rows'] as $row) {
            $flags[$row['projet']->getNom()] = $row['atRisk'];
        }
        $this->assertTrue($flags['Risky']);
        $this->assertFalse($flags['Calm']);
    }

    public function testTodayIsAlwaysOnTheAxisAndMonthsAreLabelled(): void
    {
        $timeline = (new TimelineBuilder())->build([$this->projet('Past', '2026-01-10', '2026-02-20', 'terminé')], $this->today);

        $this->assertNotNull($timeline['today']);
        $this->assertEqualsWithDelta(100.0, $timeline['today'], 0.01, 'today is the end of the axis when every project is in the past');
        $this->assertSame('02/2026', $timeline['months'][0]['label']);
    }
}
