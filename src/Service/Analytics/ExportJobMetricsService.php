<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\Entity\Analytics\ExportJob;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Aggregates export job metrics for operational monitoring.
 *
 * This service scans a recent slice of export jobs and derives summary metrics that can be
 * exposed through an API endpoint or fed into higher-level monitoring integrations.
 */
final readonly class ExportJobMetricsService
{
    /**
     * @param EntityManagerInterface $entityManager doctrine entity manager used to read export jobs
     */
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Builds a metrics snapshot for recent export jobs.
     *
     * The resulting payload is intentionally scalar-heavy so it can be returned as JSON without
     * additional normalization.
     *
     * @return array{
     *   jobs_total:int,
     *   status_counts:array{pending:int,running:int,done:int,failed:int},
     *   retryable_failed_jobs:int,
     *   avg_duration_ms:int,
     *   exported_rows_total:int
     * }
     */
    public function snapshot(): array
    {
        $repository = $this->entityManager->getRepository(ExportJob::class);
        $jobs = $repository->findBy([], ['created_at' => 'DESC'], 200);

        $statusCounts = [
            ExportJob::STATUS_PENDING => 0,
            ExportJob::STATUS_RUNNING => 0,
            ExportJob::STATUS_DONE => 0,
            ExportJob::STATUS_FAILED => 0,
        ];

        $durationSum = 0;
        $durationCount = 0;
        $retryableFailed = 0;
        $exportedRows = 0;

        foreach ($jobs as $job) {
            $status = $job->getStatus();
            if (isset($statusCounts[$status])) {
                ++$statusCounts[$status];
            }

            $payload = $job->getPayload() ?? [];
            if (isset($payload['duration_ms']) && is_numeric($payload['duration_ms'])) {
                $durationSum += (int) $payload['duration_ms'];
                ++$durationCount;
            }
            if (isset($payload['row_count']) && is_numeric($payload['row_count'])) {
                $exportedRows += (int) $payload['row_count'];
            }
            if ($job->canRetry()) {
                ++$retryableFailed;
            }
        }

        return [
            'jobs_total' => count($jobs),
            'status_counts' => $statusCounts,
            'retryable_failed_jobs' => $retryableFailed,
            'avg_duration_ms' => 0 === $durationCount ? 0 : (int) round($durationSum / $durationCount),
            'exported_rows_total' => $exportedRows,
        ];
    }
}
