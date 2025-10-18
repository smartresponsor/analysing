<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

final class ReportExporterService
{
    /**
     * @param array $rows
     * @param \App\Service\Analytics\string $format
     * @param \App\Service\Analytics\string|null $dir
     * @return string path to file
     */
    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $dir = $dir ?? sys_get_temp_dir();
        if (!is_dir($dir)) { mkdir($dir, 0777, true); }
        $ts = (new DateTimeImmutable())->format('Ymd_His');
        $filename = $format === 'xlsx' ? "analytics_report_{$ts}.xlsx" : "analytics_report_{$ts}.csv";
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if ($format === 'csv' || $format === 'xlsx') {
            $this->writeCsv($rows, $path, $delimiter = ',');
            return $path;
        }
        throw new InvalidArgumentException('Unsupported format: ' . $format);
    }

    /** naive CSV writer used for both csv and xlsx fallback */
    private function writeCsv(array $rows, string $path, string $delimiter = ','): void
    {
        $fh = fopen($path, 'w');
        if ($fh === false) {
            throw new RuntimeException('Cannot open file for writing: ' . $path);
        }
        // header
        if (!empty($rows)) {
            fputcsv($fh, array_keys($rows[0]), $delimiter);
        }
        foreach ($rows as $r) {
            fputcsv($fh, array_map(static function ($v) {
                if (is_bool($v)) return $v ? 'true' : 'false';
                if ($v instanceof DateTimeInterface) return $v->format('c');
                return (string)$v;
            }, $r), $delimiter);
        }
        fclose($fh);
    }
}
