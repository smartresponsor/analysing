<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\ReportExporterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportExporterServiceTest extends TestCase
{
    public function testExportWritesCsvFile(): void
    {
        $dir = sys_get_temp_dir().'/analytics_export_'.uniqid('', true);
        mkdir($dir, 0777, true);

        $service = new ReportExporterService(new NullLogger());
        $path = $service->export([
            ['metric' => 'sales', 'value' => 10],
            ['metric' => 'refunds', 'value' => 2],
        ], 'csv', $dir);

        self::assertFileExists($path);
        self::assertStringContainsString('metric,value', (string) file_get_contents($path));

        @unlink($path);
        @rmdir($dir);
    }

    public function testExportRejectsUnsupportedFormat(): void
    {
        $service = new ReportExporterService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->export([], 'xlsx');
    }
}
