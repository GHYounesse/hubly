<?php

namespace App\Tests\Monitoring;

use App\Service\Monitoring\DigestFormatter;
use PHPUnit\Framework\TestCase;

class DigestFormatterTest extends TestCase
{
    public function testAQuietDayProducesNoMessage(): void
    {
        $this->assertNull((new DigestFormatter())->format([], [], []));
    }

    public function testOnlyNonEmptySectionsAreWrittenAndDownSitesComeFirst(): void
    {
        $text = (new DigestFormatter())->format(
            [['label' => 'atlas-immo.ma', 'detail' => 'OVH, le 14/11/2026']],
            [],
            [['label' => 'Portail Nour', 'detail' => 'HTTP 503']],
        );

        $this->assertNotNull($text);
        $this->assertStringContainsString('Sites hors ligne (1)', $text);
        $this->assertStringContainsString('- Portail Nour : HTTP 503', $text);
        $this->assertStringContainsString('Hébergements qui expirent bientôt (1)', $text);
        $this->assertStringNotContainsString('Certificats SSL', $text);
        $this->assertLessThan(strpos($text, 'Hébergements'), strpos($text, 'Sites hors ligne'));
    }
}
