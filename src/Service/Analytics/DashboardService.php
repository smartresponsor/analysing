<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class DashboardService implements DashboardServiceInterface
{
    private const MAX_TIMESERIES_ROWS = 366;
    private const MAX_VENDOR_ROWS = 1000;

    public function __construct(
        private readonly Connection $db,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function kpi(KpiRequest $req): array
    {
        [$whereSql, $params] = $this->buildWhereClause($req);
        $sql = "SELECT COALESCE(SUM(gross_minor),0) AS gross,
                       COALESCE(SUM(net_minor),0)   AS net,
                       COUNT(*) AS days
                  FROM metric_snapshot
                $whereSql";

        $row = $this->fetchAssociativeOrFail($sql, $params, 'Dashboard KPI query failed.');
        $gross = $this->readRequiredInt($row, 'gross');
        $net = $this->readRequiredInt($row, 'net');
        $days = $this->readRequiredInt($row, 'days');
        $margin = $gross > 0 ? round(($net / $gross) * 100, 2) : 0.0;

        $this->logger->info('Analytics dashboard KPI query completed.', [
            'vendor_id' => $req->vendorId,
            'currency' => $req->currency,
            'from' => $req->from,
            'to' => $req->to,
            'days' => $days,
        ]);

        return [
            'gross_minor' => $gross,
            'net_minor' => $net,
            'margin_pct' => $margin,
            'days' => $days,
        ];
    }

    public function timeseries(KpiRequest $req): array
    {
        [$whereSql, $params] = $this->buildWhereClause($req);
        $sql = "SELECT date AS d, SUM(gross_minor) as gross, SUM(net_minor) as net
                  FROM metric_snapshot
                $whereSql
              GROUP BY date
              ORDER BY date ASC";

        $rows = $this->fetchAllAssociativeOrFail($sql, $params, 'Dashboard timeseries query failed.');
        if (count($rows) > self::MAX_TIMESERIES_ROWS) {
            throw new \RuntimeException('Dashboard timeseries query returned too many rows.');
        }

        $mapped = array_map(function (array $row): array {
            $dateValue = $row['d'] ?? '';
            $date = is_scalar($dateValue) ? trim((string) $dateValue) : '';
            if ('' === $date) {
                throw new \RuntimeException('Dashboard timeseries row is missing date.');
            }

            return [
                'date' => $date,
                'gross_minor' => $this->readRequiredInt($row, 'gross'),
                'net_minor' => $this->readRequiredInt($row, 'net'),
            ];
        }, $rows);

        $this->logger->info('Analytics dashboard timeseries query completed.', [
            'vendor_id' => $req->vendorId,
            'currency' => $req->currency,
            'from' => $req->from,
            'to' => $req->to,
            'rows' => count($mapped),
        ]);

        return $mapped;
    }

    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array
    {
        $normalizedCurrency = $this->normalizeOptionalCurrency($currency);
        $normalizedFrom = $this->normalizeOptionalDate($from, 'from');
        $normalizedTo = $this->normalizeOptionalDate($to, 'to');
        $this->assertOrderedOptionalRange($normalizedFrom, $normalizedTo);

        $where = [];
        $params = [];
        if (null !== $normalizedCurrency) {
            $where[] = 'currency = :c';
            $params['c'] = $normalizedCurrency;
        }
        if (null !== $normalizedFrom) {
            $where[] = 'date >= :from';
            $params['from'] = $normalizedFrom->format('Y-m-d H:i:s');
        }
        if (null !== $normalizedTo) {
            $where[] = 'date <= :to';
            $params['to'] = $normalizedTo->format('Y-m-d H:i:s');
        }
        $whereSql = [] !== $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $sql = "SELECT vendor_id, SUM(gross_minor) as gross, SUM(net_minor) as net
                  FROM metric_snapshot
                $whereSql
              GROUP BY vendor_id
              ORDER BY gross DESC";

        $rows = $this->fetchAllAssociativeOrFail($sql, $params, 'Dashboard by-vendor query failed.');
        if (count($rows) > self::MAX_VENDOR_ROWS) {
            throw new \RuntimeException('Dashboard by-vendor query returned too many rows.');
        }

        $mapped = array_map(function (array $row): array {
            $vendorId = $this->readRequiredInt($row, 'vendor_id');
            $gross = $this->readRequiredInt($row, 'gross');
            $net = $this->readRequiredInt($row, 'net');
            $margin = $gross > 0 ? round(($net / $gross) * 100, 2) : 0.0;

            return [
                'vendor_id' => $vendorId,
                'gross_minor' => $gross,
                'net_minor' => $net,
                'margin_pct' => $margin,
            ];
        }, $rows);

        $this->logger->info('Analytics dashboard by-vendor query completed.', [
            'currency' => $normalizedCurrency,
            'from' => $normalizedFrom?->format('Y-m-d H:i:s'),
            'to' => $normalizedTo?->format('Y-m-d H:i:s'),
            'rows' => count($mapped),
        ]);

        return $mapped;
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhereClause(KpiRequest $req): array
    {
        $where = [];
        $params = [];

        if (null !== $req->vendorId) {
            if ($req->vendorId <= 0) {
                throw new \InvalidArgumentException('Dashboard vendorId must be a positive integer.');
            }

            $where[] = 'vendor_id = :v';
            $params['v'] = $req->vendorId;
        }

        $normalizedCurrency = $this->normalizeOptionalCurrency($req->currency);
        if (null !== $normalizedCurrency) {
            $where[] = 'currency = :c';
            $params['c'] = $normalizedCurrency;
        }

        $normalizedFrom = $this->normalizeOptionalDate($req->from, 'from');
        $normalizedTo = $this->normalizeOptionalDate($req->to, 'to');
        $this->assertOrderedOptionalRange($normalizedFrom, $normalizedTo);

        if (null !== $normalizedFrom) {
            $where[] = 'date >= :from';
            $params['from'] = $normalizedFrom->format('Y-m-d H:i:s');
        }
        if (null !== $normalizedTo) {
            $where[] = 'date <= :to';
            $params['to'] = $normalizedTo->format('Y-m-d H:i:s');
        }

        return [[] !== $where ? ('WHERE '.implode(' AND ', $where)) : '', $params];
    }

    private function normalizeOptionalCurrency(?string $currency): ?string
    {
        if (null === $currency) {
            return null;
        }

        $normalized = strtoupper(trim($currency));
        if ('' === $normalized) {
            return null;
        }

        if (1 !== preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw new \InvalidArgumentException('Dashboard currency must be a 3-letter ISO code.');
        }

        return $normalized;
    }

    private function normalizeOptionalDate(?string $value, string $field): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            return null;
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('Dashboard %s must be a valid date/time string.', $field), 0, $exception);
        }
    }

    private function assertOrderedOptionalRange(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): void
    {
        if (null !== $from && null !== $to && $from > $to) {
            throw new \InvalidArgumentException('Dashboard "from" must be earlier than or equal to "to".');
        }
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function fetchAssociativeOrFail(string $sql, array $params, string $message): array
    {
        try {
            $row = $this->db->fetchAssociative($sql, $params);
        } catch (\Throwable $exception) {
            $this->logger->error($message, [
                'exception' => $exception,
                'sql' => $sql,
                'params' => $params,
            ]);

            throw new \RuntimeException($message, 0, $exception);
        }

        if (!is_array($row)) {
            throw new \RuntimeException('Dashboard query did not return an associative row.');
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAllAssociativeOrFail(string $sql, array $params, string $message): array
    {
        try {
            $rows = $this->db->fetchAllAssociative($sql, $params);
        } catch (\Throwable $exception) {
            $this->logger->error($message, [
                'exception' => $exception,
                'sql' => $sql,
                'params' => $params,
            ]);

            throw new \RuntimeException($message, 0, $exception);
        }

        if (!is_array($rows)) {
            throw new \RuntimeException('Dashboard query did not return a row set.');
        }

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new \RuntimeException(sprintf('Dashboard query returned an invalid row at index %d.', $index));
            }
        }

        return array_values($rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readRequiredInt(array $row, string $field): int
    {
        if (!array_key_exists($field, $row)) {
            throw new \RuntimeException(sprintf('Dashboard query row is missing field "%s".', $field));
        }

        if (!is_scalar($row[$field]) && null !== $row[$field]) {
            throw new \RuntimeException(sprintf('Dashboard query field "%s" must be scalar.', $field));
        }

        return (int) $row[$field];
    }
}
