<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Exports analytics report rows into CSV files.
 *
 * The exporter performs lightweight validation, normalizes headers across heterogeneous rows and
 * writes the resulting CSV either to a generated temporary file or to a caller-provided path.
 */
final class ReportExporterService implements ReportExporterServiceInterface
{
    private const MAX_EXPORT_ROWS = 10000;
    private const MAX_COLUMNS = 256;
    private const MAX_COLUMN_NAME_LENGTH = 128;

    /**
     * @param LoggerInterface $logger Logger used for exporter-related diagnostics.
     */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Writes report rows into a generated CSV file inside the target directory.
     *
     * @param list<array<string,mixed>> $rows   Normalized or partially normalized export rows.
     * @param string                    $format Output format. Only CSV is currently supported.
     * @param string|null               $dir    Optional target directory for the generated file.
     *
     * @return string Absolute path to the generated export file.
     */
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

    /**
     * Writes report rows directly to the requested file path.
     *
     * @param list<array<string,mixed>> $rows       Rows to serialize.
     * @param string                    $targetPath Final target path for the generated file.
     * @param string                    $format     Output format. Only CSV is supported.
     *
     * @return string The target path that was written.
     */
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

    /**
     * Serializes rows as CSV using the provided delimiter.
     *
     * @param list<array<string,mixed>> $rows      Rows to serialize.
     * @param string                    $path      File path to write.
     * @param string                    $delimiter Delimiter used by fputcsv.
     */
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

    /**
     * Normalizes the requested export format.
     *
     * @param string $format User-provided export format.
     *
     * @return string Normalized export format.
     */
    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ($normalized === '' || $normalized === 'csv') {
            return 'csv';
        }

        throw new \InvalidArgumentException('Unsupported export format');
    }

    /**
     * Normalizes export rows and validates header keys.
     *
     * @param list<array<string,mixed>> $rows Source rows.
     * @param string                    $path Target path used for diagnostics.
     *
     * @return list<array<string,mixed>>
     */
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

    /**
     * Builds the full header set across all rows.
     *
     * @param list<array<string,mixed>> $rows Normalized rows.
     *
     * @return list<string>
     */
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
