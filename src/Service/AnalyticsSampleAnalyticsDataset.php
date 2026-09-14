<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\ServiceInterface\AnalyticsSampleAnalyticsDatasetInterface;

final class AnalyticsSampleAnalyticsDataset implements AnalyticsSampleAnalyticsDatasetInterface
{
    /**
     * @return array{gross_minor:int,net_minor:int,margin_pct:float,days:int}
     */
    public function kpi(AnalyticsKpiRequestDTO $request): array
    {
        $baseGross = 182500;
        $gross = null !== $request->vendorId ? (int) round($baseGross * 0.42) : $baseGross;
        $net = (int) round($gross * 0.68);

        return [
            'gross_minor' => $gross,
            'net_minor' => $net,
            'margin_pct' => $gross > 0 ? round(($net / $gross) * 100, 2) : 0.0,
            'days' => 7,
        ];
    }

    /**
     * @return list<array{date:string,gross_minor:int,net_minor:int}>
     *
     * @throws \Exception
     */
    public function timeseries(AnalyticsKpiRequestDTO $request): array
    {
        $start = null !== $request->from
            ? new \DateTimeImmutable($request->from)
            : new \DateTimeImmutable('2026-01-01 00:00:00');

        $rows = [];
        for ($offset = 0; $offset < 7; ++$offset) {
            $day = $start->modify(sprintf('+%d day', $offset));
            $gross = 18000 + ($offset * 1750);
            if (null !== $request->vendorId) {
                $gross = (int) round($gross * 0.42);
            }
            $net = (int) round($gross * 0.67);
            $rows[] = [
                'date' => $day->format('Y-m-d'),
                'gross_minor' => $gross,
                'net_minor' => $net,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{vendor_id:int,gross_minor:int,net_minor:int,margin_pct:float}>
     */
    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array
    {
        $rows = [
            ['vendor_id' => 101, 'gross_minor' => 62000, 'net_minor' => 43100],
            ['vendor_id' => 205, 'gross_minor' => 57150, 'net_minor' => 38290],
            ['vendor_id' => 309, 'gross_minor' => 48900, 'net_minor' => 33741],
        ];

        return array_map(static function (array $row): array {
            $gross = $row['gross_minor'];
            $net = $row['net_minor'];

            return [
                'vendor_id' => $row['vendor_id'],
                'gross_minor' => $gross,
                'net_minor' => $net,
                'margin_pct' => round(($net / $gross) * 100, 2),
            ];
        }, $rows);
    }

    /**
     * @param string             $app
     * @param string             $env
     * @param list<string>       $steps
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     *
     * @return list<array{day:string,user_count:int}>
     *
     * @throws \DateMalformedStringException
     */
    public function funnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = [];
        $cursor = $from->setTime(0, 0);
        $limit = min(5, max(1, (int) $to->diff($from)->format('%a') + 1));

        for ($offset = 0; $offset < $limit; ++$offset) {
            $rows[] = [
                'day' => $cursor->modify(sprintf('+%d day', $offset))->format('Y-m-d'),
                'user_count' => max(15, 250 - ($offset * 37)),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{day_offset:int,active_user:int}>
     */
    public function retention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array
    {
        $limit = min(max($days, 1), 7);
        $rows = [];
        for ($offset = 0; $offset < $limit; ++$offset) {
            $rows[] = [
                'day_offset' => $offset,
                'active_user' => max(7, 120 - ($offset * 13)),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    public function path(string $app, string $env, \DateTimeImmutable $day, int $top): array
    {
        $rows = [
            ['from_event' => 'view', 'to_event' => 'add_to_cart', 'transition_count' => 144],
            ['from_event' => 'add_to_cart', 'to_event' => 'checkout', 'transition_count' => 93],
            ['from_event' => 'checkout', 'to_event' => 'purchase', 'transition_count' => 61],
            ['from_event' => 'purchase', 'to_event' => 'thank_you', 'transition_count' => 59],
        ];

        return array_slice($rows, 0, max(1, min($top, count($rows))));
    }
}
