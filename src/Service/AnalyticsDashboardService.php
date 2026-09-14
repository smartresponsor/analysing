<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\Entity\Analytics\AnalyticsDashboardMetricSnapshotEntity;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

final class AnalyticsDashboardService implements AnalyticsDashboardServiceInterface
{
    private const int MAX_TIMESERIES_ROWS = 366;
    private const int MAX_VENDOR_ROWS = 1000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function kpi(AnalyticsKpiRequestDTO $req): array
    {
        [$predicates, $params] = $this->buildCriteria($req);
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(snapshot.grossMinor), 0) AS gross')
            ->addSelect('COALESCE(SUM(snapshot.netMinor), 0) AS net')
            ->addSelect('COUNT(snapshot.id) AS days')
            ->from(AnalyticsDashboardMetricSnapshotEntity::class, 'snapshot');
        $this->applyCriteria($qb, $predicates, $params);

        $row = $this->fetchSingleScalarRow($qb, 'Dashboard KPI query failed.');
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

    public function timeseries(AnalyticsKpiRequestDTO $req): array
    {
        [$predicates, $params] = $this->buildCriteria($req);
        $qb = $this->em->createQueryBuilder()
            ->select('snapshot.snapshotDate AS d')
            ->addSelect('SUM(snapshot.grossMinor) AS gross')
            ->addSelect('SUM(snapshot.netMinor) AS net')
            ->from(AnalyticsDashboardMetricSnapshotEntity::class, 'snapshot')
            ->groupBy('snapshot.snapshotDate')
            ->orderBy('snapshot.snapshotDate', 'ASC');
        $this->applyCriteria($qb, $predicates, $params);

        $rows = $this->fetchScalarRows($qb, 'Dashboard timeseries query failed.');
        if (count($rows) > self::MAX_TIMESERIES_ROWS) {
            throw new \RuntimeException('Dashboard timeseries query returned too many rows.');
        }

        $mapped = array_map(function (array $row): array {
            $date = $this->normalizeDateResult($row, 'd');

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

        $qb = $this->em->createQueryBuilder()
            ->select('snapshot.vendorId AS vendor_id')
            ->addSelect('SUM(snapshot.grossMinor) AS gross')
            ->addSelect('SUM(snapshot.netMinor) AS net')
            ->from(AnalyticsDashboardMetricSnapshotEntity::class, 'snapshot')
            ->groupBy('snapshot.vendorId')
            ->orderBy('gross', 'DESC');

        if (null !== $normalizedCurrency) {
            $qb->andWhere('snapshot.currency = :currency');
            $qb->setParameter('currency', $normalizedCurrency);
        }
        if (null !== $normalizedFrom) {
            $qb->andWhere('snapshot.snapshotDate >= :from');
            $qb->setParameter('from', $normalizedFrom);
        }
        if (null !== $normalizedTo) {
            $qb->andWhere('snapshot.snapshotDate <= :to');
            $qb->setParameter('to', $normalizedTo);
        }

        $rows = $this->fetchScalarRows($qb, 'Dashboard by-vendor query failed.');
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
     * @return array{0:list<string>,1:array<string,mixed>}
     */
    private function buildCriteria(AnalyticsKpiRequestDTO $req): array
    {
        $where = [];
        $params = [];

        if (null !== $req->vendorId) {
            if ($req->vendorId <= 0) {
                throw new \InvalidArgumentException('Dashboard vendorId must be a positive integer.');
            }

            $where[] = 'snapshot.vendorId = :vendor';
            $params['vendor'] = $req->vendorId;
        }

        $normalizedCurrency = $this->normalizeOptionalCurrency($req->currency);
        if (null !== $normalizedCurrency) {
            $where[] = 'snapshot.currency = :currency';
            $params['currency'] = $normalizedCurrency;
        }

        $normalizedFrom = $this->normalizeOptionalDate($req->from, 'from');
        $normalizedTo = $this->normalizeOptionalDate($req->to, 'to');
        $this->assertOrderedOptionalRange($normalizedFrom, $normalizedTo);

        if (null !== $normalizedFrom) {
            $where[] = 'snapshot.snapshotDate >= :from';
            $params['from'] = $normalizedFrom;
        }
        if (null !== $normalizedTo) {
            $where[] = 'snapshot.snapshotDate <= :to';
            $params['to'] = $normalizedTo;
        }

        return [$where, $params];
    }

    /**
     * @param list<string>        $predicates
     * @param array<string,mixed> $params
     */
    private function applyCriteria(QueryBuilder $qb, array $predicates, array $params): void
    {
        foreach ($predicates as $predicate) {
            $qb->andWhere($predicate);
        }

        foreach ($params as $name => $value) {
            $qb->setParameter($name, $value);
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchScalarRows(QueryBuilder $qb, string $message): array
    {
        try {
            $rows = array_values($qb->getQuery()->getScalarResult());
            $normalized = [];
            foreach ($rows as $row) {
                $normalized[] = $this->normalizeScalarRow($row);
            }

            return $normalized;
        } catch (\Throwable $exception) {
            $this->logger->error($message, [
                'exception' => $exception,
            ]);

            throw new \RuntimeException($message, 0, $exception);
        }
    }

    /**
     * @param mixed $row
     *
     * @return array<string, mixed>
     */
    private function normalizeScalarRow(mixed $row): array
    {
        if (!is_array($row)) {
            throw new \RuntimeException('Dashboard query row must be an array.');
        }

        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchSingleScalarRow(QueryBuilder $qb, string $message): array
    {
        $rows = $this->fetchScalarRows($qb, $message);
        if ([] === $rows) {
            throw new \RuntimeException($message);
        }

        return $rows[0];
    }

    /**
     * @param array<string,mixed> $row
     */
    private function normalizeDateResult(array $row, string $field): string
    {
        if (!array_key_exists($field, $row)) {
            throw new \RuntimeException(sprintf('Dashboard query row is missing field "%s".', $field));
        }

        $value = $row[$field];
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_scalar($value)) {
            $normalized = trim((string) $value);
            if ('' !== $normalized) {
                return substr($normalized, 0, 10);
            }
        }

        throw new \RuntimeException(sprintf('Dashboard query field "%s" must be a date value.', $field));
    }

    /**
     * @param array<string,mixed> $row
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
}
