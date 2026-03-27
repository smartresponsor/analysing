<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\GzipWriterInterface;
use Psr\Log\LoggerInterface;

final class GzipWriter implements GzipWriterInterface
{
    private const MAX_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function write(string $path, array $rows): string
    {
        if (count($rows) > self::MAX_ROWS) {
            $this->logger->error('Analytics gzip writer rejected an oversized row set.', [
                'rows' => count($rows),
                'max_rows' => self::MAX_ROWS,
                'path' => $path,
            ]);

            throw new \RuntimeException('Analytics gzip writer exceeded the maximum supported row count.');
        }

        $directory = \dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->logger->error('Analytics gzip writer could not create target directory.', [
                'directory' => $directory,
                'path' => $path,
            ]);

            throw new \RuntimeException('Unable to create gzip target directory.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ana-');
        if (false === $tmp) {
            $this->logger->error('Analytics gzip writer could not create temporary file.', [
                'path' => $path,
            ]);

            throw new \RuntimeException('Unable to create temporary gzip file.');
        }

        $handle = fopen($tmp, 'w');
        if (false === $handle) {
            $this->logger->error('Analytics gzip writer could not open temporary file.', [
                'tmp' => $tmp,
                'path' => $path,
            ]);
            $this->cleanupTemporaryFile($tmp);

            throw new \RuntimeException('Unable to open temporary gzip file.');
        }

        try {
            foreach ($rows as $index => $row) {
                if (!is_array($row) || [] === $row) {
                    $this->logger->error('Analytics gzip writer rejected an invalid row shape.', [
                        'path' => $path,
                        'row_index' => $index,
                        'row_type' => get_debug_type($row),
                    ]);

                    throw new \RuntimeException('Analytics gzip writer row must be a non-empty array.');
                }
                try {
                    $json = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    $this->logger->error('Analytics gzip writer failed to encode row.', [
                        'path' => $path,
                        'row_index' => $index,
                        'exception' => $exception,
                    ]);

                    throw new \RuntimeException('Unable to encode gzip row.', 0, $exception);
                }

                if (false === fwrite($handle, $json.'
')) {
                    $this->logger->error('Analytics gzip writer failed to write temporary row.', [
                        'tmp' => $tmp,
                        'path' => $path,
                    ]);

                    throw new \RuntimeException('Unable to write gzip temporary file.');
                }
            }
        } finally {
            fclose($handle);
        }

        $gz = $path.'.gz';
        $input = fopen($tmp, 'r');
        if (false === $input) {
            $this->cleanupTemporaryFile($tmp);
            $this->logger->error('Analytics gzip writer could not reopen temporary file.', [
                'tmp' => $tmp,
                'path' => $path,
            ]);

            throw new \RuntimeException('Unable to reopen temporary gzip file.');
        }

        $output = gzopen($gz, 'wb9');
        if (false === $output) {
            fclose($input);
            $this->cleanupTemporaryFile($tmp);
            $this->logger->error('Analytics gzip writer could not open target gzip file.', [
                'path' => $gz,
            ]);

            throw new \RuntimeException('Unable to open target gzip file.');
        }

        try {
            while (!feof($input)) {
                $chunk = fread($input, 8192);
                if (false === $chunk) {
                    $this->logger->error('Analytics gzip writer failed while reading temporary file.', [
                        'tmp' => $tmp,
                        'path' => $gz,
                    ]);

                    throw new \RuntimeException('Unable to read temporary gzip file.');
                }

                if ('' === $chunk) {
                    continue;
                }

                if (false === gzwrite($output, $chunk)) {
                    $this->logger->error('Analytics gzip writer failed while writing gzip file.', [
                        'path' => $gz,
                    ]);

                    throw new \RuntimeException('Unable to write gzip file.');
                }
            }
        } finally {
            fclose($input);
            gzclose($output);
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
