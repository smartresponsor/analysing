<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Command\AnalyticsExportCommand;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AnalyticsExportCommandTest extends TestCase
{
    public function testExecuteExportsCsvToTargetPath(): void
    {
        $dashboard = $this->createMock(DashboardServiceInterface::class);
        $dashboard->method('kpi')->willReturn([
            'gross_minor' => 100,
            'net_minor' => 80,
            'margin_pct' => '20.0',
            'days' => 31,
        ]);
        $dashboard->method('timeseries')->willReturn([
            ['date' => '2026-01-01', 'gross_minor' => 10, 'net_minor' => 8],
        ]);

        $generatedPath = tempnam(sys_get_temp_dir(), 'analytics-generated-');
        self::assertNotFalse($generatedPath);
        file_put_contents($generatedPath, "section,date\n");

        $exporter = $this->createMock(ReportExporterServiceInterface::class);
        $exporter->method('export')->willReturn($generatedPath);

        $command = new AnalyticsExportCommand($dashboard, $exporter, $this->createMock(LoggerInterface::class));
        $tester = new CommandTester($command);

        $targetBase = sys_get_temp_dir().'/analytics-export-'.uniqid('', true);
        $status = $tester->execute(['path' => $targetBase]);

        $targetPath = $targetBase.'.csv';
        self::assertSame(0, $status);
        self::assertFileExists($targetPath);

        @unlink($targetPath);
    }
}
