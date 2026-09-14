<?php

declare(strict_types=1);

namespace App\Analysing\Controller;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Service\AnalyticsExportJobMetricsService;
use App\Analysing\Service\AnalyticsExportJobView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides HTTP endpoints for interacting with analytics export jobs.
 */
final readonly class AnalyticsExportJobController implements AnalyticsExportJobControllerInterface
{
    /**
     * @param EntityManagerInterface           $entityManager doctrine entity manager for job retrieval
     * @param AnalyticsExportJobMetricsService $metrics       service providing aggregated metrics
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnalyticsExportJobMetricsService $metrics,
    ) {
    }

    /**
     * Returns the current status of an export job.
     *
     * @param int $id export job identifier
     *
     * @return JsonResponse JSON representation of the export job
     */
    #[Route('/api/analytics/export/jobs/{id}', methods: ['GET'])]
    public function status(int $id): JsonResponse
    {
        $job = $this->entityManager->getRepository(AnalyticsExportJobEntity::class)->find($id);
        if (!$job instanceof AnalyticsExportJobEntity) {
            return new JsonResponse(['message' => 'Export job not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(AnalyticsExportJobView::toArray($job));
    }

    /**
     * Returns aggregated export job metrics.
     *
     * @return JsonResponse metrics snapshot payload
     */
    #[Route('/api/analytics/export/jobs/metrics', methods: ['GET'])]
    public function metrics(): JsonResponse
    {
        return new JsonResponse($this->metrics->snapshot());
    }
}
