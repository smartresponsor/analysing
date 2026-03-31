<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\ReportExporterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportExporterServiceTest extends TestCase
{
    public function testExportPreservesColumnsIntroducedAfterTheFirstRow(): void
    {
        $service = new ReportExporterService(new NullLogger());
        $dir = sys_get_temp_dir().'/analytics_export_'.bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);

        $path = $service->export([
            ['section' => 'totals', 'gross_minor' => 1000],
            ['section' => 'timeseries', 'date' => '2026-03-10', 'gross_minor' => 100],
        ], 'csv', $dir);

        $csv = (string) file_get_contents($path);
        self::assertStringContainsString('date', $csv);
        self::assertStringContainsString('2026-03-10', $csv);

        @unlink($path);
        @rmdir($dir);
    }
}
