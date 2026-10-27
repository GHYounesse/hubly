<?php

namespace App\Tests\Export;

use App\Service\Export\CsvExporter;
use PHPUnit\Framework\TestCase;

class CsvExporterTest extends TestCase
{
    private CsvExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new CsvExporter();
    }

    public function testExcelFriendlyFormat(): void
    {
        $csv = $this->exporter->toString(['Nom', 'Statut'], [['Atlas', 'en cours']]);

        $this->assertStringStartsWith(CsvExporter::BOM, $csv, 'UTF-8 BOM so Excel reads accents correctly');
        $this->assertSame("Nom;Statut\r\nAtlas;\"en cours\"\r\n", substr($csv, strlen(CsvExporter::BOM)));
    }

    public function testValuesAreEscaped(): void
    {
        $csv = $this->exporter->toString(['Note'], [['dit "oui"; puis non']]);

        $this->assertStringContainsString('"dit ""oui""; puis non"', $csv);
    }

    public function testNullsAndBooleansAreReadable(): void
    {
        $csv = $this->exporter->toString(['a', 'b', 'c'], [[null, true, false]]);

        $this->assertStringContainsString("\r\n;oui;non\r\n", $csv);
    }

    public function testFormulasAreNeutralised(): void
    {
        $csv = $this->exporter->toString(['Nom'], [['=HYPERLINK("http://evil.test")'], ['@SUM(A1)'], ['-12.5']]);

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@SUM(A1)", $csv);
        $this->assertStringContainsString("\r\n-12.5\r\n", $csv, 'a plain negative number stays a number');
    }
}
