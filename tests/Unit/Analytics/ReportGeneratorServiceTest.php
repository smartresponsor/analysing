<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\ExportJob;
use App\Analysing\Service\Analytics\ReportGeneratorService;
use App\Analysing\ServiceInterface\Analytics\DashboardServiceInterface;
use App\Analysing\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportGeneratorServiceTest extends TestCase
{
    public function testGenerateReturnsCompletedExportJob(): void
    {
        $dashboard = $this->createMock(DashboardServiceInterface::class);
        $dashboard->expects(self::once())
            ->method('kpi')
            ->willReturn([
                'gross_minor' => 1200,
                'net_minor' => 900,
                'margin_pct' => 25,
                'days' => 7,
            ]);
        $dashboard->expects(self::once())
            ->method('timeseries')
            ->willReturn([
                ['date' => '2026-01-01', 'gross_minor' => 500, 'net_minor' => 400],
            ]);

        $exportPath = tempnam(sys_get_temp_dir(), 'analytics-report-');
        self::assertNotFalse($exportPath);

        $exporter = $this->createMock(ReportExporterServiceInterface::class);
        $exporter->expects(self::once())
            ->method('export')
            ->willReturn($exportPath);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(ExportJob::class));
        $em->expects(self::exactly(2))->method('flush');

        $service = new ReportGeneratorService($dashboard, $exporter, $em, new NullLogger());
        $job = $service->generate([
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-07 23:59:59',
            'vendorId' => 42,
            'currency' => 'usd',
            'format' => 'csv',
        ]);

        self::assertSame('done', $job->getStatus());
        self::assertSame(1, $job->getAttempts());
        self::assertNull($job->getError());

        @unlink($exportPath);
    }

    public function testGenerateRejectsUnsupportedFormat(): void
    {
        $service = new ReportGeneratorService(
            $this->createMock(DashboardServiceInterface::class),
            $this->createMock(ReportExporterServiceInterface::class),
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->generate([
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-07 23:59:59',
            'format' => 'xlsx',
        ]);
    }
}
