<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ReportExporterService implements ReportExporterServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    public function export(array $rows, string $format = 'csv', ?string $dir = null): string
    {
        $format = $this->normalizeFormat($format);
        $dir = $dir ?? sys_get_temp_dir();
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->logger->error('Analytics report exporter could not create export directory.', [
                'directory' => $dir,
                'format' => $format,
            ]);

            throw new RuntimeException('Cannot create export directory: ' . $dir);
        }

        $ts = (new DateTimeImmutable())->format('Ymd_His');
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "analytics_report_{$ts}.csv";
        $this->writeCsv($rows, $path, ',');

        return $path;
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function writeCsv(array $rows, string $path, string $delimiter = ','): void
    {
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            $this->logger->error('Analytics report exporter could not open target file.', [
                'path' => $path,
            ]);

            throw new RuntimeException('Cannot open file for writing: ' . $path);
        }

        try {
            if ($rows !== [] && fputcsv($fh, array_keys($rows[0]), $delimiter) === false) {
                $this->logger->error('Analytics report exporter failed to write CSV header.', [
                    'path' => $path,
                ]);

                throw new RuntimeException('Cannot write CSV header: ' . $path);
            }

            foreach ($rows as $index => $row) {
                try {
                    $serializedRow = array_map(static function (mixed $value): string {
                        if (is_bool($value)) {
                            return $value ? 'true' : 'false';
                        }
                        if ($value instanceof DateTimeInterface) {
                            return $value->format(DATE_ATOM);
                        }
                        if (is_array($value)) {
                            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                        return (string) $value;
                    }, $row);
                } catch (JsonException $exception) {
                    $this->logger->error('Analytics report exporter failed to encode CSV row payload.', [
                        'path' => $path,
                        'row_index' => $index,
                        'exception' => $exception,
                    ]);

                    throw new RuntimeException('Cannot encode CSV row payload.', 0, $exception);
                }

                $result = fputcsv($fh, $serializedRow, $delimiter);
                if ($result === false) {
                    $this->logger->error('Analytics report exporter failed to write CSV row.', [
                        'path' => $path,
                        'row_index' => $index,
                    ]);

                    throw new RuntimeException('Cannot write CSV row: ' . $path);
                }
            }
        } finally {
            fclose($fh);
        }
    }

    private function normalizeFormat(string $format): string
    {
        $normalized = strtolower(trim($format));
        if ($normalized === '') {
            $this->logger->warning('Analytics report exporter received an empty format. Falling back to csv.');

            return 'csv';
        }

        if ($normalized !== 'csv') {
            $this->logger->error('Analytics report exporter received an unsupported format.', [
                'format' => $format,
                'normalized' => $normalized,
            ]);

            throw new InvalidArgumentException('Unsupported export format: ' . $format);
        }

        return $normalized;
    }
}
