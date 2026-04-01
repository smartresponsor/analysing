<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\ExportJob;

/**
 * Transforms an {@see ExportJob} entity into an API-friendly array payload.
 *
 * Keeping this mapping logic in a dedicated view helper avoids duplicating response-shaping code
 * inside controllers and makes response contracts easier to evolve over time.
 */
final class ExportJobView
{
    /**
     * Converts an export job into a normalized array representation.
     *
     * @param ExportJob $job The export job entity to normalize.
     *
     * @return array{
     *   id:int|null,
     *   type:string,
     *   status:string,
     *   attempts:int,
     *   error:?string,
     *   created_at:string,
     *   finished_at:?string,
     *   payload:array<string,mixed>,
     *   download_url:?string
     * }
     */
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
