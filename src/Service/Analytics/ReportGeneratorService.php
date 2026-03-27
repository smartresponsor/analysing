<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\Entity\Analytics\ExportJob;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use App\ServiceInterface\Analytics\ReportGeneratorServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ReportGeneratorService implements ReportGeneratorServiceInterface
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    public function generate(array $params): ExportJob
    {
        $startedAt = microtime(true);
        $normalizedParams = $this->normalizeParams($params);
        $format = $this->normalizeFormat($normalizedParams['format'] ?? 'csv');
        $job = new ExportJob($format, $normalizedParams);
        $job->incAttempts();

        try {
            $job->start();
            $this->em->persist($job);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report job bootstrap failed.', [
                'exception' => $exception,
                'params' => $normalizedParams,
                'format' => $format,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ]);

            throw new \RuntimeException('Analytics report job bootstrap failed.', 0, $exception);
        }

        try {
            $dto = new KpiRequest(
                vendorId: $normalizedParams['vendorId'] ?? null,
                currency: $normalizedParams['currency'] ?? null,
                from: $normalizedParams['from'],
                to: $normalizedParams['to'],
            );

            $kpi = $this->dashboard->kpi($dto);
            $series = $this->dashboard->timeseries($dto);
            $rows = [[
                'section' => 'totals',
                'from' => $normalizedParams['from'],
                'to' => $normalizedParams['to'],
                'vendor_id' => $normalizedParams['vendorId'] ?? '',
                'currency' => $normalizedParams['currency'] ?? '',
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

            if ([] === $rows) {
                $this->logger->warning('Analytics report generation produced no rows before export.', [
                    'params' => $normalizedParams,
                    'format' => $format,
                ]);
            }

            $exportPath = $this->exporter->export($rows, $format);
            if ('' === $exportPath || !is_file($exportPath)) {
                throw new \RuntimeException('Analytics report export path is missing after export.');
            }
            $job->done();
            $this->logger->info('Analytics report generation completed.', [
                'format' => $format,
                'rows' => count($rows),
                'params' => $normalizedParams,
                'export_path' => $exportPath,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report generation failed.', [
                'exception' => $exception,
                'params' => $normalizedParams,
                'format' => $format,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ]);
            $job->fail($exception->getMessage());
        }

        try {
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics report job final flush failed.', [
                'exception' => $exception,
                'job_status' => $job->getStatus(),
                'attempts' => $job->getAttempts(),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
                'params' => $normalizedParams,
                'format' => $format,
            ]);

            throw new \RuntimeException('Analytics report job final flush failed.', 0, $exception);
        }

        return $job;
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

        if (array_key_exists('vendorId', $params) && null !== $params['vendorId'] && '' != $params['vendorId']) {
            if (is_int($params['vendorId'])) {
                $vendorId = $params['vendorId'];
            } elseif (is_string($params['vendorId']) && 1 === preg_match('/^\d+$/', $params['vendorId'])) {
                $vendorId = (int) $params['vendorId'];
            } else {
                throw new \InvalidArgumentException('Report parameter "vendorId" must be a positive integer.');
            }

            if ($vendorId <= 0) {
                throw new \InvalidArgumentException('Report parameter "vendorId" must be a positive integer.');
            }

            $normalized['vendorId'] = $vendorId;
        }

        if (array_key_exists('currency', $params) && null !== $params['currency'] && '' !== $params['currency']) {
            if (!is_string($params['currency'])) {
                throw new \InvalidArgumentException('Report parameter "currency" must be a string.');
            }

            $currency = strtoupper(trim($params['currency']));
            if (1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new \InvalidArgumentException('Report parameter "currency" must be a 3-letter ISO code.');
            }

            $normalized['currency'] = $currency;
        }

        if (array_key_exists('format', $params)) {
            $normalized['format'] = is_string($params['format']) ? trim($params['format']) : (string) $params['format'];
        }

        return $normalized;
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
