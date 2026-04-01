<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\Entity\Analytics\ExportJob;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Executes export jobs in a worker-friendly context.
 *
 * The runner turns persisted export job payloads into concrete dashboard queries, generates the
 * export artifact, and updates the job state with runtime metadata such as duration and output path.
 */
final class ExportJobRunner
{
    /**
     * @param DashboardServiceInterface    $dashboard      Service used to compute KPI aggregates.
     * @param ReportExporterServiceInterface $exporter     Service used to write the final export file.
     * @param ReportRowBuilder             $rowBuilder     Builder that normalizes export rows.
     * @param EntityManagerInterface       $entityManager  Entity manager used to persist job state transitions.
     * @param LoggerInterface              $logger         Logger used for execution failures.
     */
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly ReportRowBuilder $rowBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Runs the provided export job and persists its final state.
     *
     * @param ExportJob $job The job to execute.
     *
     * @return ExportJob The same job instance after it has been updated with result metadata.
     */
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
