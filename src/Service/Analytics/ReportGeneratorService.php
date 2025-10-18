<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\Entity\Analytics\ExportJob;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final class ReportGeneratorService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ReportExporterService $exporter,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * @param array{from:string,to:string,vendorId?:int,currency?:string,format?:string} $params
     */
    public function generate(array $params): ExportJob
    {
        $format = $params['format'] ?? 'csv';
        $job = new ExportJob($format, $params);
        $this->em->persist($job);
        $this->em->flush();

        try {
            $dto = new KpiRequest(
                vendorId: $params['vendorId'] ?? null,
                currency: $params['currency'] ?? null,
                from: $params['from'] ?? null,
                to: $params['to'] ?? null
            );

            // Build dataset: header + totals + by day
            $kpi = $this->dashboard->kpi($dto);
            $series = $this->dashboard->timeseries($dto);
            $rows = [];

            $rows[] = [
                'section' => 'totals',
                'from' => $params['from'],
                'to' => $params['to'],
                'vendor_id' => $params['vendorId'] ?? '',
                'currency' => $params['currency'] ?? '',
                'gross_minor' => $kpi['gross_minor'],
                'net_minor' => $kpi['net_minor'],
                'margin_pct' => $kpi['margin_pct'],
                'days' => $kpi['days'],
            ];

            foreach ($series as $p) {
                $rows[] = [
                    'section' => 'timeseries',
                    'date' => $p['date'],
                    'gross_minor' => $p['gross_minor'],
                    'net_minor' => $p['net_minor'],
                ];
            }

            $path = $this->exporter->export($rows, $format);
            $job->markDone($path);
        } catch (Throwable $e) {
            $job->markFailed();
        }

        $this->em->flush();
        return $job;
    }
}
