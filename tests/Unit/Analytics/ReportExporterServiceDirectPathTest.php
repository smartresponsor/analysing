<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsReportExporterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportExporterServiceDirectPathTest extends TestCase
{
    public function testExportToPathWritesExactlyToRequestedTarget(): void
    {
        $service = new AnalyticsReportExporterService(new NullLogger());
        $dir = sys_get_temp_dir().'/analytics_export_to_path_'.bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        $targetPath = $dir.'/report.csv';

        $writtenPath = $service->exportToPath([
            ['section' => 'totals', 'gross_minor' => 1000],
            ['section' => 'timeseries', 'date' => '2026-03-10', 'gross_minor' => 100],
        ], $targetPath, 'csv');

        self::assertSame($targetPath, $writtenPath);
        self::assertFileExists($targetPath);

        $csv = (string) file_get_contents($targetPath);
        self::assertStringContainsString('date', $csv);
        self::assertStringContainsString('2026-03-10', $csv);

        @unlink($targetPath);
        @rmdir($dir);
    }
}
