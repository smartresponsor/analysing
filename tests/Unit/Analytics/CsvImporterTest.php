<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsCsvImporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CsvImporterTest extends TestCase
{
    public function testReadParsesRowsByHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');
        file_put_contents($path, "name,value\nfoo,10\nbar,20\n");

        $service = new AnalyticsCsvImporter(new NullLogger());
        $rows = $service->read($path);

        self::assertCount(2, $rows);
        self::assertSame('foo', $rows[0]['name']);
        self::assertSame('20', $rows[1]['value']);

        @unlink($path);
    }

    public function testReadRejectsDuplicateHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');
        file_put_contents($path, "name,name\nfoo,10\n");

        $service = new AnalyticsCsvImporter(new NullLogger());

        $this->expectException(\RuntimeException::class);
        try {
            $service->read($path);
        } finally {
            @unlink($path);
        }
    }
}
