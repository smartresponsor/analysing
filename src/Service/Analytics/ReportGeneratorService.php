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

/**
 * Generates analytics export jobs in a synchronous application flow.
 *
 * This service validates raw generation parameters, creates an {@see ExportJob}, computes KPI and
 * timeseries data, exports the result and persists runtime metadata back to the job payload.
 */
final class ReportGeneratorService implements ReportGeneratorServiceInterface
{
    /**
     * @param DashboardServiceInterface      $dashboard Service used to calculate KPIs and timeseries data.
     * @param ReportExporterServiceInterface $exporter  Service used to persist generated exports.
     * @param ReportRowBuilder               $rowBuilder Builder used to normalize export rows.
     * @param EntityManagerInterface         $em        Entity manager used to persist export jobs.
     * @param LoggerInterface                $logger    Logger reserved for future runtime diagnostics.
     */
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly ReportRowBuilder $rowBuilder,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generates an export job from raw analytics parameters.
     *
     * @param array{from:string,to:string,vendorId?:int|string,currency?:string,format?:string} $params Raw report parameters.
     *
     * @return ExportJob A persisted export job enriched with execution metadata.
     *
     * @throws \RuntimeException If the export job cannot be initialized.
     */
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

    /**
     * Validates and normalizes incoming report parameters.
     *
     * @param array<string,mixed> $params Raw user-provided parameters.
     *
     * @return array{from:string,to:string,vendorId?:int,currency?:string,format?:string}
     */
    private function normalizeParams(array $params): array
    {
        $from = new \DateTimeImmutable($params['from']);
        $to = new \DateTimeImmutable($params['to']);

        return [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Normalizes the requested export format.
     *
     * @param string $format User-provided format value.
     *
     * @return string Normalized export format.
     */
    private function normalizeFormat(string $format): string
    {
        return 'csv';
    }
}
