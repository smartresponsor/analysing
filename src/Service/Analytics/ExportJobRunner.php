<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\Entity\Analytics\ExportJob;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ExportJobRunner
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly ReportRowBuilder $rowBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(ExportJob $job): ExportJob
    {
        $startedAt = microtime(true);
        $payload = $job->getPayload() ?? [];
        $format = strtolower(trim((string) ($payload['format'] ?? $job->getType())));
        if ('' === $format) {
            $format = 'csv';
        }

        $job->incAttempts();
        $job->start();
        $this->entityManager->flush();

        try {
            $dto = new KpiRequest(
                vendorId: isset($payload['vendorId']) && is_numeric($payload['vendorId']) ? (int) $payload['vendorId'] : null,
                currency: is_string($payload['currency'] ?? null) ? $payload['currency'] : null,
                from: is_string($payload['from'] ?? null) ? $payload['from'] : null,
                to: is_string($payload['to'] ?? null) ? $payload['to'] : null,
            );

            $kpi = $this->dashboard->kpi($dto);
            $series = $this->dashboard->timeseries($dto);
            $rows = $this->rowBuilder->build($dto, $kpi, $series);
            $exportPath = $this->exporter->export($rows, $format);

            $job->mergePayload([
                'export_path' => $exportPath,
                'row_count' => count($rows),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
            $job->done();
        } catch (\Throwable $exception) {
            $job->fail($exception->getMessage());
            $job->mergePayload([
                'failure_message' => $job->getError(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            $this->logger->error('Analytics export job run failed.', [
                'exception' => $exception,
                'job_id' => $job->getId(),
                'status' => $job->getStatus(),
            ]);
        }

        $this->entityManager->flush();

        return $job;
    }
}
