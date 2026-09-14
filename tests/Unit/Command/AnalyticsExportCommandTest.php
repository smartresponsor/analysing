<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Command;

use App\Analysing\Command\AnalyticsExportCommand;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;
use App\Analysing\ServiceInterface\AnalyticsReportExporterServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

final class AnalyticsExportCommandTest extends TestCase
{
    public function testCommandUsesExportToPath(): void
    {
        $dashboard = $this->createMock(AnalyticsDashboardServiceInterface::class);
        $dashboard->method('kpi')->willReturn(['gross_minor' => 0, 'net_minor' => 0, 'margin_pct' => 0.0, 'days' => 0]);
        $dashboard->method('timeseries')->willReturn([]);

        $tmpDir = sys_get_temp_dir().'/analytics-export-'.bin2hex(random_bytes(4));
        mkdir($tmpDir, 0777, true);
        $generatedPath = $tmpDir.'/generated.csv';
        file_put_contents($generatedPath, "section,date\n");

        $exporter = $this->createMock(AnalyticsReportExporterServiceInterface::class);
        $exporter
            ->expects(self::once())
            ->method('export')
            ->with(self::isType('array'), 'csv', $tmpDir)
            ->willReturn($generatedPath);

        $targetPath = $tmpDir.'/test.csv';

        $command = new AnalyticsExportCommand(
            $dashboard,
            $exporter,
            new NullLogger()
        );

        $tester = new CommandTester($command);
        $tester->execute(['path' => $targetPath]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertFileExists($targetPath);

        @unlink($targetPath);
        @rmdir($tmpDir);
    }
}
