<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\FileNotifierInterface;
use Psr\Log\LoggerInterface;

final class FileNotifier implements FileNotifierInterface
{
    private const MAX_ENDPOINT_LENGTH = 1024;
    private const MAX_PAYLOAD_BYTES = 1048576;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $logPath = '',
    ) {
    }

    public function send(string $endpoint, array $payload): bool
    {
        $endpoint = trim($endpoint);
        if ('' === $endpoint) {
            $this->logger->warning('Analytics file notifier rejected an empty endpoint.');

            return false;
        }

        if (mb_strlen($endpoint) > self::MAX_ENDPOINT_LENGTH) {
            $this->logger->warning('Analytics file notifier rejected an overlong endpoint.', [
                'length' => mb_strlen($endpoint),
                'max_length' => self::MAX_ENDPOINT_LENGTH,
            ]);

            return false;
        }

        $path = $this->resolvePath();
        $directory = \dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->logger->error('Analytics file notifier could not create log directory.', [
                'endpoint' => $endpoint,
                'directory' => $directory,
            ]);

            return false;
        }

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (strlen($json) > self::MAX_PAYLOAD_BYTES) {
                $this->logger->warning('Analytics file notifier rejected an oversized payload.', [
                    'endpoint' => $endpoint,
                    'bytes' => strlen($json),
                    'max_bytes' => self::MAX_PAYLOAD_BYTES,
                ]);

                return false;
            }
        } catch (\JsonException $exception) {
            $this->logger->error('Analytics file notifier failed to encode payload.', [
                'endpoint' => $endpoint,
                'exception' => $exception,
            ]);

            return false;
        }

        $line = sprintf('%s|%s|%s%s', (new \DateTimeImmutable())->format(DATE_ATOM), $endpoint, $json, PHP_EOL);
        if (false === file_put_contents($path, $line, FILE_APPEND | LOCK_EX)) {
            $this->logger->error('Analytics file notifier failed to append log line.', [
                'endpoint' => $endpoint,
                'path' => $path,
            ]);

            return false;
        }

        $this->logger->info('Analytics file notifier appended payload.', [
            'endpoint' => $endpoint,
            'path' => $path,
            'bytes' => strlen($line),
        ]);

        return true;
    }

    private function resolvePath(): string
    {
        if ('' !== $this->logPath) {
            return $this->logPath;
        }

        return sys_get_temp_dir().'/analytics/notify.log';
    }
}
