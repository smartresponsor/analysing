<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobMetricsService;
use App\Service\Analytics\ExportJobView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides HTTP endpoints for interacting with analytics export jobs.
 */
final readonly class ExportJobController
{
    /**
     * @param EntityManagerInterface  $entityManager doctrine entity manager for job retrieval
     * @param ExportJobMetricsService $metrics       service providing aggregated metrics
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExportJobMetricsService $metrics,
    ) {
    }

    /**
     * Returns the current status of an export job.
     *
     * @param int $id export job identifier
     *
     * @return JsonResponse JSON representation of the export job
     */
    #[Route('/api/analytics/export-jobs/{id<\\d+>}', methods: ['GET'])]
    public function status(int $id): JsonResponse
    {
        $job = $this->entityManager->getRepository(ExportJob::class)->find($id);
        if (!$job instanceof ExportJob) {
            return new JsonResponse(['message' => 'Export job not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(ExportJobView::toArray($job));
    }

    /**
     * Returns aggregated export job metrics.
     *
     * @return JsonResponse metrics snapshot payload
     */
    #[Route('/api/analytics/export-jobs/metrics', methods: ['GET'])]
    public function metrics(): JsonResponse
    {
        return new JsonResponse($this->metrics->snapshot());
    }
}
