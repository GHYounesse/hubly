<?php

namespace App\Service\Project;

use App\Entity\Projet;

/**
 * Places projects on a time axis: each bar goes from the order date (or the creation date) to the
 * delivery date, as a percentage of the whole period so the template only needs CSS.
 */
final class TimelineBuilder
{
    private const MINIMUM_WIDTH = 1.5;

    /**
     * @param iterable<Projet> $projets projects without a delivery date are left out
     *
     * @return array{
     *     rows: list<array{projet: Projet, left: float, width: float, overdue: bool, atRisk: bool, start: \DateTimeImmutable, end: \DateTimeImmutable}>,
     *     start: ?\DateTimeImmutable,
     *     end: ?\DateTimeImmutable,
     *     today: ?float,
     *     months: list<array{label: string, left: float}>
     * }
     */
    public function build(iterable $projets, \DateTimeImmutable $today): array
    {
        $items = [];
        foreach ($projets as $projet) {
            $end = $projet->getDateLivraison();
            if ($end === null) {
                continue;
            }

            $end = \DateTimeImmutable::createFromInterface($end)->setTime(0, 0);
            $startSource = $projet->getDateCommande() ?? $projet->getCreated();
            $start = $startSource ? \DateTimeImmutable::createFromInterface($startSource)->setTime(0, 0) : $end->modify('-30 days');

            if ($start > $end) {
                $start = $end; // inconsistent dates: show a short bar on the delivery date
            }

            $items[] = ['projet' => $projet, 'start' => $start, 'end' => $end];
        }

        if ($items === []) {
            return ['rows' => [], 'start' => null, 'end' => null, 'today' => null, 'months' => []];
        }

        $min = min(array_map(static fn (array $i) => $i['start'], $items));
        $max = max(array_map(static fn (array $i) => $i['end'], $items));
        // The axis always includes today, so the "today" marker is visible
        $min = min($min, $today->setTime(0, 0));
        $max = max($max, $today->setTime(0, 0));
        $span = max(1, $max->getTimestamp() - $min->getTimestamp());

        $percent = static fn (\DateTimeImmutable $date): float => round(($date->getTimestamp() - $min->getTimestamp()) / $span * 100, 2);

        $rows = array_map(function (array $item) use ($percent, $today): array {
            $left = $percent($item['start']);
            $width = max(self::MINIMUM_WIDTH, $percent($item['end']) - $left);

            return [
                'projet' => $item['projet'],
                'start' => $item['start'],
                'end' => $item['end'],
                'left' => min($left, 100 - self::MINIMUM_WIDTH),
                'width' => min($width, 100 - $left),
                'overdue' => $item['end'] < $today->setTime(0, 0) && $item['projet']->getStatus() !== 'terminé',
                'atRisk' => trim((string) $item['projet']->getRisqueLivraison()) !== '',
            ];
        }, $items);

        usort($rows, static fn (array $a, array $b) => [$a['end'], $a['start']] <=> [$b['end'], $b['start']]);

        return [
            'rows' => $rows,
            'start' => $min,
            'end' => $max,
            'today' => $percent($today->setTime(0, 0)),
            'months' => $this->months($min, $max, $percent),
        ];
    }

    /** @return list<array{label: string, left: float}> */
    private function months(\DateTimeImmutable $min, \DateTimeImmutable $max, callable $percent): array
    {
        $months = [];
        $cursor = $min->modify('first day of this month')->setTime(0, 0);

        while ($cursor <= $max && count($months) < 36) {
            if ($cursor >= $min) {
                $months[] = ['label' => $cursor->format('m/Y'), 'left' => $percent($cursor)];
            }
            $cursor = $cursor->modify('first day of next month');
        }

        return $months;
    }
}
