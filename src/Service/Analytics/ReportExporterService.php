<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Psr\Log\LoggerInterface;

final class ReportExporterService implements ReportExporterServiceInterface
{
    private const MAX_EXPORT_ROWS = 10000;
    private const MAX_COLUMNS = 256;
    private const MAX_COLUMN_NAME_LENGTH = 128;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $format = $this->normalizeFormat($format);
        if (count($rows) > self::MAX_EXPORT_ROWS) {
            throw new \InvalidArgumentException('Analytics report export exceeds the maximum supported row count.');
        }

        $dir = $dir ?? sys_get_temp_dir();
        $ts = (new \DateTimeImmutable())->format('Ymd_His');
        $path = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."analytics_report_{$ts}.csv";

        $this->writeCsv($rows, $path, ',');

        return $path;
    }

    public function exportToPath(array $rows, string $targetPath, string $format = 'csv'): string
    {
        $format = $this->normalizeFormat($format);
        if (count($rows) > self::MAX_EXPORT_ROWS) {
            throw new \InvalidArgumentException('Analytics report export exceeds the maximum supported row count.');
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create export directory: '.$dir);
        }

        $this->writeCsv($rows, $targetPath, ',');

        return $targetPath;
    }

    private function writeCsv(array $rows, string $path, string $delimiter = ','): void
    {
        $fh = fopen($path, 'w');
        if (false === $fh) {
            throw new \RuntimeException('Cannot open file for writing: '.$path);
        }

        try {
            $normalizedRows = $this->normalizeRows($rows, $path);
            $headers = $this->normalizeHeaders($normalizedRows);

            if ([] !== $headers) {
                fputcsv($fh, $headers, $delimiter);
            }

            foreach ($normalizedRows as $row) {
                $aligned = [];
                foreach ($headers as $h) {
                    $aligned[] = (string)($row[$h] ?? '');
                }
                fputcsv($fh, $aligned, $delimiter);
            }
        } finally {
            fclose($fh);
        }
    }

    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ($normalized === '' || $normalized === 'csv') {
            return 'csv';
        }

        throw new \InvalidArgumentException('Unsupported export format');
    }

    private function normalizeRows(array $rows, string $path): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new \RuntimeException('Invalid row');
            }

            $clean = [];
            foreach ($row as $k => $v) {
                $key = trim((string)$k);
                if ($key === '') {
                    throw new \RuntimeException('Empty header');
                }
                if (mb_strlen($key) > self::MAX_COLUMN_NAME_LENGTH) {
                    throw new \RuntimeException('Header too long');
                }
                $clean[$key] = $v;
            }

            $normalized[] = $clean;
        }

        return $normalized;
    }

    private function normalizeHeaders(array $rows): array
    {
        $headers = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $h) {
                if (!in_array($h, $headers, true)) {
                    $headers[] = $h;
                }
            }
        }

        if (count($headers) > self::MAX_COLUMNS) {
            throw new \RuntimeException('Too many columns');
        }

        return $headers;
    }
}
