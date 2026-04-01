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

final class ExportJobController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExportJobMetricsService $metrics,
    ) {
    }

    #[Route('/api/analytics/export-jobs/{id<\d+>}', methods: ['GET'])]
    public function status(int $id): JsonResponse
    {
        $job = $this->entityManager->getRepository(ExportJob::class)->find($id);
        if (!$job instanceof ExportJob) {
            return new JsonResponse(['message' => 'Export job not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(ExportJobView::toArray($job));
    }

    #[Route('/api/analytics/export-jobs/metrics', methods: ['GET'])]
    public function metrics(): JsonResponse
    {
        return new JsonResponse($this->metrics->snapshot());
    }
}
