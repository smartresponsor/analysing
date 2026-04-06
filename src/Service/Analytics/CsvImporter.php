<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\CsvImporterInterface;
use Psr\Log\LoggerInterface;

final class CsvImporter implements CsvImporterInterface
{
    private const int MAX_ROWS = 10000;
    private const int MAX_COLUMNS = 256;
    private const int MAX_FIELD_LENGTH = 4096;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function read(string $csvPath, string $delimiter = ','): array
    {
        $rows = [];
        $rowCount = 0;
        $delimiter = $this->normalizeDelimiter($delimiter);

        if (!is_file($csvPath)) {
            $this->logger->warning('Analytics CSV import file is missing.', ['path' => $csvPath]);

            $this->logger->info('Analytics CSV import completed.', [
                'path' => $csvPath,
                'rows' => count($rows),
                'columns' => 0,
            ]);

            return $rows;
        }

        $handle = fopen($csvPath, 'r');
        if (false === $handle) {
            $this->logger->error('Analytics CSV import file could not be opened.', ['path' => $csvPath]);
            throw new \RuntimeException('Unable to open analytics CSV file.');
        }

        try {
            $headers = $this->readHeaders($handle, $csvPath, $delimiter);
            if ([] === $headers) {
                return $rows;
            }

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($rowCount >= self::MAX_ROWS) {
                    $this->logger->warning('Analytics CSV import stopped at the maximum supported row count.', [
                        'path' => $csvPath,
                        'max_rows' => self::MAX_ROWS,
                    ]);
                    break;
                }
                if ($data === [null] || [] === $data) {
                    continue;
                }

                $rows[] = $this->mapRow($headers, $data, $csvPath);
                ++$rowCount;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function normalizeDelimiter(string $delimiter): string
    {
        $delimiter = trim($delimiter);
        if ('' === $delimiter) {
            $this->logger->warning('Analytics CSV import delimiter is empty. Falling back to comma delimiter.');

            return ',';
        }

        return mb_substr($delimiter, 0, 1);
    }

    /**
     * @param resource $handle
     *
     * @return list<string>
     */
    private function readHeaders($handle, string $csvPath, string $delimiter): array
    {
        $headers = fgetcsv($handle, 0, $delimiter);
        if (false === $headers || $headers === [null]) {
            $this->logger->warning('Analytics CSV import file has no readable header row.', ['path' => $csvPath]);

            return [];
        }

        $normalized = [];
        if (count($headers) > self::MAX_COLUMNS) {
            $this->logger->error('Analytics CSV import header exceeds the maximum supported column count.', [
                'path' => $csvPath,
                'columns' => count($headers),
                'max_columns' => self::MAX_COLUMNS,
            ]);

            throw new \RuntimeException('Analytics CSV header exceeds the maximum supported column count.');
        }
        foreach ($headers as $index => $name) {
            $header = trim((string) $name);
            $header = ltrim($header, "\xEF\xBB\xBF");
            if ('' === $header) {
                $header = 'column_'.($index + 1);
            }

            $normalized[] = mb_substr($header, 0, self::MAX_FIELD_LENGTH);
        }

        if (count(array_unique($normalized)) !== count($normalized)) {
            $this->logger->error('Analytics CSV import header row contains duplicate column names.', [
                'path' => $csvPath,
                'headers' => $normalized,
            ]);

            throw new \RuntimeException('Analytics CSV header contains duplicate columns.');
        }

        return $normalized;
    }

    /**
     * @param list<string>      $headers
     * @param list<string|null> $data
     *
     * @return array<string, string>
     */
    private function mapRow(array $headers, array $data, string $csvPath): array
    {
        if (count($data) !== count($headers)) {
            $this->logger->warning('Analytics CSV row width does not match header width.', [
                'path' => $csvPath,
                'header_count' => count($headers),
                'row_count' => count($data),
            ]);
        }

        if (count($headers) > self::MAX_COLUMNS) {
            throw new \RuntimeException('Analytics CSV header exceeds the maximum supported column count.');
        }

        $row = [];
        foreach ($headers as $index => $name) {
            $value = (string) ($data[$index] ?? '');
            if (mb_strlen($value) > self::MAX_FIELD_LENGTH) {
                $this->logger->warning('Analytics CSV import truncated an overlong field value.', [
                    'path' => $csvPath,
                    'column' => $name,
                    'max_length' => self::MAX_FIELD_LENGTH,
                ]);
                $value = mb_substr($value, 0, self::MAX_FIELD_LENGTH);
            }

            $row[$name] = $value;
        }

        return $row;
    }
}
