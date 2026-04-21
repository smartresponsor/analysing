<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\GzipWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class GzipWriterTest extends TestCase
{
    public function testWriteCreatesGzipFile(): void
    {
        $dir = sys_get_temp_dir().'/analytics_gzip_'.uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir.'/events.jsonl';

        $writer = new GzipWriter(new NullLogger());
        $gzPath = $writer->write($path, [['event' => 'purchase']]);

        self::assertFileExists($gzPath);
        self::assertStringEndsWith('.gz', $gzPath);

        @unlink($gzPath);
        @rmdir($dir);
    }

    public function testWriteRejectsInvalidRowShape(): void
    {
        $dir = sys_get_temp_dir().'/analytics_gzip_'.uniqid('', true);
        mkdir($dir, 0777, true);
        $path = $dir.'/events.jsonl';

        $writer = new GzipWriter(new NullLogger());

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
}
