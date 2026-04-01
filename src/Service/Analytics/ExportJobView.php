<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\ExportJob;

final class ExportJobView
{
    public static function toArray(ExportJob $job): array
    {
        $payload = $job->getPayload() ?? [];

        return [
            'id' => $job->getId(),
            'type' => $job->getType(),
            'status' => $job->getStatus(),
            'attempts' => $job->getAttempts(),
            'error' => $job->getError(),
            'created_at' => $job->getCreatedAt()->format(DATE_ATOM),
            'finished_at' => $job->getFinishedAt()?->format(DATE_ATOM),
            'payload' => $payload,
            'download_url' => isset($payload['export_path']) ? '/api/analytics/export-jobs/'.$job->getId().'/download' : null,
        ];
    }
}
