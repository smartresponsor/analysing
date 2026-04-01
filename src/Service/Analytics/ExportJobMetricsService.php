<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\ExportJob;
use Doctrine\ORM\EntityManagerInterface;

final class ExportJobMetricsService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

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
