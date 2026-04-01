<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\Entity\Analytics\ExportJob;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class ExportJobController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/analytics/export-jobs/{id<\d+>}', name: 'analytics_export_job_status', methods: ['GET'])]
    public function status(int $id): JsonResponse
    {
        $job = $this->entityManager->getRepository(ExportJob::class)->find($id);
        if (!$job instanceof ExportJob) {
            return new JsonResponse(['message' => 'Export job not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $job->getPayload() ?? [];
        $exportPath = is_string($payload['export_path'] ?? null) ? $payload['export_path'] : null;

        return new JsonResponse([
            'id' => $job->getId(),
            'type' => $job->getType(),
            'status' => $job->getStatus(),
            'attempts' => $job->getAttempts(),
            'error' => $job->getError(),
            'created_at' => $job->getCreatedAt()->format(DATE_ATOM),
            'finished_at' => $job->getFinishedAt()?->format(DATE_ATOM),
            'payload' => $payload,
            'download_url' => $exportPath ? '/api/analytics/export-jobs/'.$id.'/download' : null,
        ]);
    }

    #[Route('/api/analytics/export-jobs/{id<\d+>}/download', name: 'analytics_export_job_download', methods: ['GET'])]
    public function download(int $id): Response
    {
        $job = $this->entityManager->getRepository(ExportJob::class)->find($id);
        if (!$job instanceof ExportJob) {
            return new JsonResponse(['message' => 'Export job not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $job->getPayload() ?? [];
        $exportPath = is_string($payload['export_path'] ?? null) ? $payload['export_path'] : null;
        if (null === $exportPath || '' === trim($exportPath)) {
            return new JsonResponse(['message' => 'Export file is not available for this job.'], Response::HTTP_CONFLICT);
        }

        if (!is_file($exportPath)) {
            return new JsonResponse(['message' => 'Export file is missing on disk.'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($exportPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportPath));

        return $response;
    }
}
