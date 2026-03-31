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

    /**
     * @param list<array<string,mixed>> $rows
     */
    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $format = $this->normalizeFormat($format);
        if (count($rows) > self::MAX_EXPORT_ROWS) {
            throw new \InvalidArgumentException('Analytics report export exceeds the maximum supported row count.');
        }
        $dir = $dir ?? sys_get_temp_dir();
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->logger->error('Analytics report exporter could not create export directory.', [
                'directory' => $dir,
                'format' => $format,
            ]);

            throw new \RuntimeException('Cannot create export directory: '.$dir);
        }

        $ts = (new \DateTimeImmutable())->format('Ymd_His');
        $path = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."analytics_report_{$ts}.csv";
        $this->writeCsv($rows, $path, ',');
        $this->logger->info('Analytics report exporter wrote CSV export.', [
            'path' => $path,
            'rows' => count($rows),
            'columns' => [] === $rows ? 0 : count(array_keys($this->normalizeRows($rows, $path)[0] ?? [])),
        ]);

        return $path;
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function writeCsv(array $rows, string $path, string $delimiter = ','): void
    {
        $fh = fopen($path, 'w');
        if (false === $fh) {
            $this->logger->error('Analytics report exporter could not open target file.', [
                'path' => $path,
            ]);

            throw new \RuntimeException('Cannot open file for writing: '.$path);
        }

        try {
            $normalizedRows = $this->normalizeRows($rows, $path);
            $headers = $this->normalizeHeaders($normalizedRows);
            if ([] !== $headers && false === fputcsv($fh, $headers, $delimiter)) {
                $this->logger->error('Analytics report exporter failed to write CSV header.', [
                    'path' => $path,
                ]);

                throw new \RuntimeException('Cannot write CSV header: '.$path);
            }

            foreach ($normalizedRows as $index => $row) {
                $row = $this->alignRowToHeaders($row, $headers);
                try {
                    $serializedRow = array_map(static function (mixed $value): string {
                        if (is_bool($value)) {
                            return $value ? 'true' : 'false';
                        }
                        if ($value instanceof \DateTimeInterface) {
                            return $value->format(DATE_ATOM);
                        }
                        if (is_array($value)) {
                            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                        return (string) $value;
                    }, $row);
                } catch (\JsonException $exception) {
                    $this->logger->error('Analytics report exporter failed to encode CSV row payload.', [
                        'path' => $path,
                        'row_index' => $index,
                        'exception' => $exception,
                    ]);

                    throw new \RuntimeException('Cannot encode CSV row payload.', 0, $exception);
                }

                $result = fputcsv($fh, $serializedRow, $delimiter);
                if (false === $result) {
                    $this->logger->error('Analytics report exporter failed to write CSV row.', [
                        'path' => $path,
                        'row_index' => $index,
                    ]);

                    throw new \RuntimeException('Cannot write CSV row: '.$path);
                }
            }
        } finally {
            fclose($fh);
        }
    }

    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ('' === $normalized) {
            $this->logger->warning('Analytics report exporter received an empty format. Falling back to csv.');

            return 'csv';
        }

        if ('csv' !== $normalized) {
            $this->logger->error('Analytics report exporter received an unsupported format.', [
                'format' => $format,
                'normalized' => $normalized,
            ]);

            throw new \InvalidArgumentException('Unsupported export format: '.$format);
        }

        return $normalized;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    private function normalizeRows(array $rows, string $path): array
    {
        $normalizedRows = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->error('Analytics report exporter rejected an invalid row shape.', [
                    'path' => $path,
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);

                throw new \RuntimeException('Analytics report exporter row must be an array.');
            }

            $normalized = [];
            foreach ($row as $header => $value) {
                $name = trim((string) $header);
                if ('' === $name) {
                    throw new \RuntimeException('Analytics report exporter header names must not be empty.');
                }
                if (mb_strlen($name) > self::MAX_COLUMN_NAME_LENGTH) {
                    $this->logger->error('Analytics report exporter rejected an overlong header name.', [
                        'path' => $path,
                        'header' => $name,
                        'max_length' => self::MAX_COLUMN_NAME_LENGTH,
                    ]);

                    throw new \RuntimeException('Analytics report exporter header name exceeds the maximum supported length.');
                }
                if (array_key_exists($name, $normalized)) {
                    throw new \RuntimeException('Analytics report exporter row contains duplicate normalized header names.');
                }

                $normalized[$name] = $value;
            }

            $normalizedRows[] = $normalized;
        }

        return $normalizedRows;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<string>
     */
    private function normalizeHeaders(array $rows): array
    {
        if ([] === $rows) {
            return [];
        }

        $headers = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $name) {
                if (!in_array($name, $headers, true)) {
                    $headers[] = $name;
                }
            }
        }

        if (count($headers) > self::MAX_COLUMNS) {
            throw new \RuntimeException('Analytics report exporter exceeds the maximum supported column count.');
        }

        return $headers;
    }

    /**
     * @param array<string,mixed> $row
     * @param list<string>        $headers
     *
     * @return array<string,mixed>
     */
    private function alignRowToHeaders(array $row, array $headers): array
    {
        $aligned = [];
        foreach ($headers as $header) {
            $aligned[$header] = $row[$header] ?? null;
        }

        return $aligned;
    }
}
