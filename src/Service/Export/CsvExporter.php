<?php

namespace App\Service\Export;

/**
 * Builds CSV files that open correctly in Excel with a French locale:
 * semicolon separator, UTF-8 byte-order mark, CRLF line ends.
 */
final class CsvExporter
{
    public const BOM = "\xEF\xBB\xBF";

    /**
     * @param list<string>                  $headers
     * @param iterable<list<scalar|null>>   $rows
     */
    public function toString(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        $this->writeLine($handle, $headers);
        foreach ($rows as $row) {
            $this->writeLine($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return self::BOM . $csv;
    }

    /** @param resource $handle */
    private function writeLine($handle, array $values): void
    {
        $values = array_map(fn ($value) => $this->cell($value), $values);
        fputcsv($handle, $values, ';', '"', '\\', "\r\n");
    }

    /**
     * A cell starting with = + - or @ would be run as a formula by Excel (CSV injection):
     * prefixing it with an apostrophe keeps it as text.
     */
    private function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }

        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($text) ? "'" . $text : $text;
    }
}
