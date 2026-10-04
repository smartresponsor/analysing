<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsGzipWriterInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsGzipWriter implements AnalyticsGzipWriterInterface
{
    private const int MAX_ROWS = 10000;
    private const int STREAM_CHUNK_BYTES = 8192;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function write(string $path, array $rows): string
    {
        $this->validateRowCount($path, $rows);
        $this->ensureTargetDirectory($path);

        $tmp = $this->createTemporaryFile($path);
        $gz = $path.'.gz';

        try {
            $this->writeRowsToTemporaryFile($tmp, $path, $rows);
            $this->compressTemporaryFile($tmp, $gz);
        } finally {
            $this->cleanupTemporaryFile($tmp);
        }

        clearstatcache(true, $gz);
        $this->logger->info('Analytics gzip writer completed.', [
            'path' => $gz,
            'rows' => count($rows),
            'bytes' => is_file($gz) ? filesize($gz) : null,
        ]);

        return $gz;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function validateRowCount(string $path, array $rows): void
    {
        $rowCount = count($rows);
        if ($rowCount <= self::MAX_ROWS) {
            return;
        }

        $this->logger->error('Analytics gzip writer rejected an oversized row set.', [
            'rows' => $rowCount,
            'max_rows' => self::MAX_ROWS,
            'path' => $path,
        ]);

        throw new \RuntimeException('Analytics gzip writer exceeded the maximum supported row count.');
    }

    private function ensureTargetDirectory(string $path): void
    {
        $directory = \dirname($path);
        if (is_dir($directory) || mkdir($directory, 0777, true) || is_dir($directory)) {
            return;
        }

        $this->logger->error('Analytics gzip writer could not create target directory.', [
            'directory' => $directory,
            'path' => $path,
        ]);

        throw new \RuntimeException('Unable to create gzip target directory.');
    }

    private function createTemporaryFile(string $path): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ana-');
        if (false !== $tmp) {
            return $tmp;
        }

        $this->logger->error('Analytics gzip writer could not create temporary file.', [
            'path' => $path,
        ]);

        throw new \RuntimeException('Unable to create temporary gzip file.');
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeRowsToTemporaryFile(string $tmp, string $path, array $rows): void
    {
        $handle = fopen($tmp, 'w');
        if (false === $handle) {
            $this->logger->error('Analytics gzip writer could not open temporary file.', [
                'tmp' => $tmp,
                'path' => $path,
            ]);

            throw new \RuntimeException('Unable to open temporary gzip file.');
        }

        try {
            foreach ($rows as $index => $row) {
                $this->writeTemporaryRow($handle, $tmp, $path, $index, $row);
            }
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function writeTemporaryRow($handle, string $tmp, string $path, int $index, mixed $row): void
    {
        if (!is_array($row) || [] === $row) {
            $this->logger->error('Analytics gzip writer rejected an invalid row shape.', [
                'path' => $path,
                'row_index' => $index,
                'row_type' => get_debug_type($row),
            ]);

            throw new \RuntimeException('Analytics gzip writer row must be a non-empty array.');
        }

        $json = $this->encodeRow($path, $index, $row);
        $this->writeStreamFully($handle, $json."\n", $tmp, $path);
    }

    /** @param array<mixed, mixed> $row */
    private function encodeRow(string $path, int $index, array $row): string
    {
        try {
            return json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Analytics gzip writer failed to encode row.', [
                'path' => $path,
                'row_index' => $index,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to encode gzip row.', 0, $exception);
        }
    }

    /** @param resource $handle */
    private function writeStreamFully($handle, string $payload, string $tmp, string $path): void
    {
        $offset = 0;
        $length = strlen($payload);

        while ($offset < $length) {
            $written = fwrite($handle, substr($payload, $offset));
            if (false === $written || 0 === $written) {
                $this->logger->error('Analytics gzip writer failed to write temporary row.', [
                    'tmp' => $tmp,
                    'path' => $path,
                ]);

                throw new \RuntimeException('Unable to write gzip temporary file.');
            }

            $offset += $written;
        }
    }

    private function compressTemporaryFile(string $tmp, string $gz): void
    {
        $input = fopen($tmp, 'r');
        if (false === $input) {
            $this->logger->error('Analytics gzip writer could not reopen temporary file.', [
                'tmp' => $tmp,
                'path' => $gz,
            ]);

            throw new \RuntimeException('Unable to reopen temporary gzip file.');
        }

        $output = gzopen($gz, 'wb9');
        if (false === $output) {
            fclose($input);
            $this->logger->error('Analytics gzip writer could not open target gzip file.', [
                'path' => $gz,
            ]);

            throw new \RuntimeException('Unable to open target gzip file.');
        }

        try {
            $this->copyToGzip($input, $output, $tmp, $gz);
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    private function copyToGzip($input, $output, string $tmp, string $gz): void
    {
        while (!feof($input)) {
            $chunk = fread($input, self::STREAM_CHUNK_BYTES);
            if (false === $chunk) {
                $this->logger->error('Analytics gzip writer failed while reading temporary file.', [
                    'tmp' => $tmp,
                    'path' => $gz,
                ]);

                throw new \RuntimeException('Unable to read temporary gzip file.');
            }

            if ('' !== $chunk) {
                $this->writeGzipFully($output, $chunk, $gz);
            }
        }
    }

    /** @param resource $output */
    private function writeGzipFully($output, string $chunk, string $gz): void
    {
        $offset = 0;
        $length = strlen($chunk);

        while ($offset < $length) {
            $written = gzwrite($output, substr($chunk, $offset));
            if (false === $written || 0 === $written) {
                $this->logger->error('Analytics gzip writer failed while writing gzip file.', [
                    'path' => $gz,
                ]);

                throw new \RuntimeException('Unable to write gzip file.');
            }

            $offset += $written;
        }
    }

    private function cleanupTemporaryFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        if (!unlink($path)) {
            $this->logger->warning('Analytics gzip writer could not remove temporary file.', [
                'tmp' => $path,
            ]);
        }
    }
}
