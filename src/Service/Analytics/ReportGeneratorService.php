<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\Entity\Analytics\ExportJob;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use App\ServiceInterface\Analytics\ReportGeneratorServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ReportGeneratorService implements ReportGeneratorServiceInterface
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly ReportRowBuilder $rowBuilder,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generate(array $params): ExportJob
    {
        $startedAt = microtime(true);
        $normalizedParams = $this->normalizeParams($params);
        $format = $this->normalizeFormat($normalizedParams['format'] ?? 'csv');

        $job = new ExportJob($format, $normalizedParams);
        $job->incAttempts();

        try {
            $job->start();
            $this->em->persist($job);
            $this->em->flush();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Analytics report job bootstrap failed.', 0, $exception);
        }

        try {
            $dto = new KpiRequest(
                vendorId: $normalizedParams['vendorId'] ?? null,
                currency: $normalizedParams['currency'] ?? null,
                from: $normalizedParams['from'],
                to: $normalizedParams['to'],
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
        }

        $this->em->flush();

        return $job;
    }

    private function normalizeParams(array $params): array
    {
        $from = new \DateTimeImmutable($params['from']);
        $to = new \DateTimeImmutable($params['to']);

        return [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeFormat(string $format): string
    {
        return 'csv';
    }
}
