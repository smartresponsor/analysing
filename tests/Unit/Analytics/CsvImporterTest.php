<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\CsvImporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CsvImporterTest extends TestCase
{
    public function testReadParsesRowsByHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');

        try {
            file_put_contents($path, "name,value\nfoo,10\nbar,20\n");

            $service = new CsvImporter(new NullLogger());
            $rows = $service->read($path);

            self::assertCount(2, $rows);
            self::assertSame('foo', $rows[0]['name']);
            self::assertSame('20', $rows[1]['value']);
        } finally {
            @unlink($path);
        }
    }

    public function testReadRejectsDuplicateHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');
        file_put_contents($path, "name,name\nfoo,10\n");

        $service = new CsvImporter(new NullLogger());

        $this->expectException(\RuntimeException::class);
        try {
            $service->read($path);
        } finally {
            @unlink($path);
        }
    }

    public function testReadReturnsEmptyArrayWhenFileIsMissing(): void
    {
        $service = new CsvImporter(new NullLogger());

        self::assertSame([], $service->read(sys_get_temp_dir().'/analytics_missing_'.bin2hex(random_bytes(4)).'.csv'));
    }

    public function testReadKeepsValuesWhenLaterRowsIntroduceWidthMismatch(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');

        try {
            file_put_contents($path, "name,value\nfoo,10,extra\nbar\n");

            $service = new CsvImporter(new NullLogger());
            $rows = $service->read($path);

            self::assertCount(2, $rows);
            self::assertSame(['name' => 'foo', 'value' => '10'], $rows[0]);
            self::assertSame(['name' => 'bar', 'value' => ''], $rows[1]);
        } finally {
            @unlink($path);
        }
    }

    public function testReadFallsBackToFirstDelimiterCharacter(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');

        try {
            file_put_contents($path, "name;value\nfoo;10\n");

            $service = new CsvImporter(new NullLogger());
            $rows = $service->read($path, '; ');

            self::assertSame('foo', $rows[0]['name']);
            self::assertSame('10', $rows[0]['value']);
        } finally {
            @unlink($path);
        }
    }

    public function testReadTruncatesOverlongFields(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');
        $value = str_repeat('a', 5000);

        try {
            file_put_contents($path, "name,value\nfoo,$value\n");

            $service = new CsvImporter(new NullLogger());
            $rows = $service->read($path);

            self::assertSame(4096, strlen($rows[0]['value']));
        } finally {
            @unlink($path);
        }
    }

    public function testReadAssignsFallbackNamesToEmptyHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'analytics_csv_');

        try {
            file_put_contents($path, ",value\nfoo,10\n");

            $service = new CsvImporter(new NullLogger());
            $rows = $service->read($path);

            self::assertSame('foo', $rows[0]['column_1']);
            self::assertSame('10', $rows[0]['value']);
        } finally {
            @unlink($path);
        }
    }
}
