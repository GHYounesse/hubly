<?php

namespace App\Service\Monitoring;

/**
 * Writes the daily summary sent by e-mail and in the team chat. Pure text, no I/O.
 */
final class DigestFormatter
{
    /**
     * Each list holds ['label' => string, 'detail' => string] items.
     *
     * @param list<array{label: string, detail: string}> $expiringHostings
     * @param list<array{label: string, detail: string}> $expiringCertificates
     * @param list<array{label: string, detail: string}> $downProjects
     *
     * @return string|null null when there is nothing to report (no message should be sent)
     */
    public function format(array $expiringHostings, array $expiringCertificates, array $downProjects): ?string
    {
        $sections = array_filter([
            'Sites hors ligne' => $downProjects,
            'Hébergements qui expirent bientôt' => $expiringHostings,
            'Certificats SSL à renouveler' => $expiringCertificates,
        ]);

        if ($sections === []) {
            return null;
        }

        $lines = ['Voici ce qui demande votre attention aujourd\'hui :', ''];
        foreach ($sections as $title => $items) {
            $lines[] = sprintf('%s (%d)', $title, count($items));
            foreach ($items as $item) {
                $lines[] = sprintf('- %s : %s', $item['label'], $item['detail']);
            }
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }
}
