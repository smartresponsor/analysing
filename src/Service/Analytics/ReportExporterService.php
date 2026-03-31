<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Psr\Log\LoggerInterface;

final class ReportExporterService implements ReportExporterServiceInterface
{
    private const MAX_EXPORT_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $dir = $dir ?? sys_get_temp_dir();
        $path = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'analytics_report_'.(new \DateTimeImmutable())->format('Ymd_His').'.csv';

        return $this->exportToPath($rows, $path, $format);
    }

    public function exportToPath(array $rows, string $targetPath, string $format = 'csv'): string
    {
        if (count($rows) > self::MAX_EXPORT_ROWS) {
            throw new \InvalidArgumentException('Too many rows');
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create export directory: '.$dir);
        }

        $fh = fopen($targetPath, 'w');
        if (false === $fh) {
            throw new \RuntimeException('Cannot open file: '.$targetPath);
        }

        try {
            $headers = [];
            foreach ($rows as $row) {
                foreach (array_keys($row) as $h) {
                    if (!in_array($h, $headers, true)) {
                        $headers[] = $h;
                    }
                }
            }

            if ([] !== $headers) {
                fputcsv($fh, $headers);
            }

            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = (string)($row[$h] ?? '');
                }
                fputcsv($fh, $line);
            }
        } finally {
            fclose($fh);
        }

        $this->logger->info('Export written', ['path' => $targetPath]);

        return $targetPath;
    }
}
