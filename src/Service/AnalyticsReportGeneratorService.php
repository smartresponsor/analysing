<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;
use App\Analysing\ServiceInterface\AnalyticsReportExporterServiceInterface;
use App\Analysing\ServiceInterface\AnalyticsReportGeneratorServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsReportGeneratorService implements AnalyticsReportGeneratorServiceInterface
{
    public function __construct(
        private AnalyticsDashboardServiceInterface $dashboard,
        private AnalyticsReportExporterServiceInterface $exporter,
        private AnalyticsExportJobRepositoryInterface $jobs,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    public function generate(array $params): AnalyticsExportJobEntity
    {
        $startedAt = microtime(true);
        $normalizedParams = $this->normalizeParams($params);
        $format = $this->normalizeFormat($normalizedParams['format'] ?? 'csv');
        $job = new AnalyticsExportJobEntity($format, $normalizedParams);
        $job->incAttempts();

        $this->startJob($job, $normalizedParams, $format, $startedAt);
        $this->generateReport($job, $normalizedParams, $format, $startedAt);
        $this->flushJob($job, $normalizedParams, $format, $startedAt);

        return $job;
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    private function startJob(AnalyticsExportJobEntity $job, array $params, string $format, float $startedAt): void
    {
        try {
            $job->start();
            $this->jobs->save($job);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report job bootstrap failed.', [
                'exception' => $exception,
                'params' => $params,
                'format' => $format,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            throw new \RuntimeException('Analytics report job bootstrap failed.', 0, $exception);
        }
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    private function generateReport(AnalyticsExportJobEntity $job, array $params, string $format, float $startedAt): void
    {
        try {
            $dto = new AnalyticsKpiRequestDTO(
                vendorId: $params['vendorId'] ?? null,
                currency: $params['currency'] ?? null,
                from: $params['from'],
                to: $params['to'],
            );
            $kpi = $this->dashboard->kpi($dto);
            $series = $this->dashboard->timeseries($dto);
            $rows = $this->buildRows($params, $kpi, $series);
            $exportPath = $this->exporter->export($rows, $format);

            if (!is_file($exportPath)) {
                throw new \RuntimeException('Analytics report export path is missing after export.');
            }

            $job->done();
            $this->logger->info('Analytics report generation completed.', [
                'format' => $format,
                'rows' => count($rows),
                'params' => $params,
                'export_path' => $exportPath,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => $this->durationMs($startedAt),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report generation failed.', [
                'exception' => $exception,
                'params' => $params,
                'format' => $format,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => $this->durationMs($startedAt),
            ]);
            $job->fail($exception->getMessage());
        }
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    private function flushJob(AnalyticsExportJobEntity $job, array $params, string $format, float $startedAt): void
    {
        try {
            $this->jobs->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report job final flush failed.', [
                'exception' => $exception,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => $this->durationMs($startedAt),
                'params' => $params,
                'format' => $format,
            ]);

            throw new \RuntimeException('Analytics report job final flush failed.', 0, $exception);
        }
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     * @param array{gross_minor:int,net_minor:int,margin_pct:int|float,days:int}         $kpi
     * @param list<array{date:string,gross_minor:int,net_minor:int}>                     $series
     *
     * @return list<array<string, int|float|string>>
     */
    private function buildRows(array $params, array $kpi, array $series): array
    {
        $rows = [[
            'section' => 'totals',
            'from' => $params['from'],
            'to' => $params['to'],
            'vendor_id' => $params['vendorId'] ?? '',
            'currency' => $params['currency'] ?? '',
            'gross_minor' => $kpi['gross_minor'],
            'net_minor' => $kpi['net_minor'],
            'margin_pct' => $kpi['margin_pct'],
            'days' => $kpi['days'],
        ]];

        foreach ($series as $point) {
            $rows[] = [
                'section' => 'timeseries',
                'date' => $point['date'],
                'gross_minor' => $point['gross_minor'],
                'net_minor' => $point['net_minor'],
            ];
        }

        return $rows;
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{from:string,to:string,vendorId?:int,currency?:string,format?:string}
     */
    private function normalizeParams(array $params): array
    {
        $from = $this->parseRequiredDate($params, 'from');
        $to = $this->parseRequiredDate($params, 'to');

        if ($from > $to) {
            throw new \InvalidArgumentException('Report parameter "from" must be earlier than or equal to "to".');
        }

        $normalized = [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        $vendorId = $this->normalizeVendorId($params['vendorId'] ?? null);
        if (null !== $vendorId) {
            $normalized['vendorId'] = $vendorId;
        }

        $currency = $this->normalizeCurrency($params['currency'] ?? null);
        if (null !== $currency) {
            $normalized['currency'] = $currency;
        }

        if (array_key_exists('format', $params)) {
            $normalized['format'] = is_scalar($params['format']) || null === $params['format'] ? trim((string) $params['format']) : '';
        }

        return $normalized;
    }

    private function normalizeVendorId(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_int($value)) {
            $vendorId = $value;
        } elseif (is_string($value) && 1 === preg_match('/^\d+$/', $value)) {
            $vendorId = (int) $value;
        } else {
            throw new \InvalidArgumentException('Report parameter "vendorId" must be a positive integer.');
        }

        if ($vendorId <= 0) {
            throw new \InvalidArgumentException('Report parameter "vendorId" must be a positive integer.');
        }

        return $vendorId;
    }

    private function normalizeCurrency(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('Report parameter "currency" must be a string.');
        }

        $currency = strtoupper(trim($value));
        if (1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Report parameter "currency" must be a 3-letter ISO code.');
        }

        return $currency;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function parseRequiredDate(array $params, string $field): \DateTimeImmutable
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('Report parameter "%s" must be a non-empty string.', $field));
        }

        try {
            return new \DateTimeImmutable(trim($value));
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('Report parameter "%s" must be a valid date/time string.', $field), 0, $exception);
        }
    }

    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ('' === $normalized) {
            return 'csv';
        }

        if ('csv' !== $normalized) {
            throw new \InvalidArgumentException('Unsupported report format: '.$format);
        }

        return $normalized;
    }
}
