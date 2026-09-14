<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsReportExporterServiceInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;

final readonly class AnalyticsReportExporterService implements AnalyticsReportExporterServiceInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $targetDir = null === $dir || '' === trim($dir) ? sys_get_temp_dir() : trim($dir);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create analytics export directory: '.$targetDir);
        }

        $path = rtrim($targetDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'analytics_export_'.$this->randomExportToken().'.'.$this->normalizeFormat($format);

        return $this->exportToPath($rows, $path, $format);
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return non-empty-string
     */
    public function exportToPath(array $rows, string $targetPath, string $format = 'csv'): string
    {
        $normalizedPath = trim($targetPath);
        if ('' === $normalizedPath) {
            throw new \InvalidArgumentException('Analytics export target path must not be empty.');
        }

        $this->normalizeFormat($format);

        $dir = dirname($normalizedPath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create analytics export target directory: '.$dir);
        }

        $handle = fopen($normalizedPath, 'w');
        if (false === $handle) {
            throw new \RuntimeException('Unable to open analytics export target path: '.$normalizedPath);
        }

        try {
            $headers = $this->collectHeaders($rows);
            if ([] !== $headers) {
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    $record = [];
                    foreach ($headers as $header) {
                        $record[] = self::normalizeCell($row[$header] ?? null);
                    }
                    fputcsv($handle, $record);
                }
            }
        } finally {
            fclose($handle);
        }

        $this->logger->info('Analytics export written.', [
            'path' => $normalizedPath,
            'rows' => count($rows),
        ]);

        return $normalizedPath;
    }

    private function randomExportToken(): string
    {
        try {
            return bin2hex(random_bytes(6));
        } catch (RandomException $exception) {
            $this->logger->warning('Analytics export token generation fell back to deterministic entropy.', [
                'exception' => $exception,
            ]);

            return substr(hash('sha256', uniqid('analytics_export_', true)), 0, 12);
        }
    }

    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ('' === $normalized) {
            $normalized = 'csv';
        }
        if ('csv' !== $normalized) {
            throw new \InvalidArgumentException('Unsupported report format: '.$format);
        }

        return $normalized;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<string>
     */
    private function collectHeaders(array $rows): array
    {
        $headers = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $_value) {
                if (!is_string($key) || '' === $key || isset($headers[$key])) {
                    continue;
                }
                $headers[$key] = $key;
            }
        }

        return array_values($headers);
    }

    private static function normalizeCell(mixed $value): string|int|float
    {
        if (null === $value) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '[unencodable]';
        }
    }
}
