<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsGzipWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class GzipWriterTest extends TestCase
{
    public function testWriteCreatesGzipFileWithDeterministicJsonLines(): void
    {
        $dir = sys_get_temp_dir().'/analytics_gzip_'.uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir.'/events.jsonl';

        $writer = new AnalyticsGzipWriter(new NullLogger());
        $gzPath = $writer->write($path, [
            ['event' => 'purchase', 'path' => '/checkout', 'label' => 'Оплата'],
            ['event' => 'refund', 'amount' => 10.5],
        ]);

        self::assertFileExists($gzPath);
        self::assertStringEndsWith('.gz', $gzPath);

        $compressed = file_get_contents($gzPath);
        self::assertIsString($compressed);
        $decoded = gzdecode($compressed);
        self::assertIsString($decoded);
        self::assertSame(
            "{\"event\":\"purchase\",\"path\":\"/checkout\",\"label\":\"Оплата\"}\n{\"event\":\"refund\",\"amount\":10.5}\n",
            $decoded,
        );

        @unlink($gzPath);
        @rmdir($dir);
    }

    public function testWriteRejectsInvalidRowShape(): void
    {
        $dir = sys_get_temp_dir().'/analytics_gzip_'.uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir.'/events.jsonl';

        $writer = new AnalyticsGzipWriter(new NullLogger());

        $this->expectException(\RuntimeException::class);
        try {
            $writer->write($path, [[]]);
        } finally {
            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
    }

    public function testWriteRejectsOversizedRowSetBeforeCreatingTargetFile(): void
    {
        $dir = sys_get_temp_dir().'/analytics_gzip_'.uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir.'/events.jsonl';
        $rows = array_fill(0, 10001, ['event' => 'purchase']);

        $writer = new AnalyticsGzipWriter(new NullLogger());

        try {
            $writer->write($path, $rows);
            self::fail('Expected oversized row set to be rejected.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'Analytics gzip writer exceeded the maximum supported row count.',
                $exception->getMessage(),
            );
            self::assertFileDoesNotExist($path.'.gz');
        } finally {
            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
    }
}
