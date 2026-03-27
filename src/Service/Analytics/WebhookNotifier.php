<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\WebhookNotifierInterface;
use Psr\Log\LoggerInterface;

final class WebhookNotifier implements WebhookNotifierInterface
{
    private const MAX_ENDPOINT_LENGTH = 1024;
    private const MAX_PAYLOAD_BYTES = 1048576;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(string $endpoint, array $payload): bool
    {
        $endpoint = trim($endpoint);
        if ('' === $endpoint) {
            $this->logger->warning('Analytics webhook notifier rejected an empty endpoint.');

            return false;
        }

        if (mb_strlen($endpoint) > self::MAX_ENDPOINT_LENGTH) {
            $this->logger->warning('Analytics webhook notifier rejected an overlong endpoint.', [
                'length' => mb_strlen($endpoint),
                'max_length' => self::MAX_ENDPOINT_LENGTH,
            ]);

            return false;
        }

        $dir = sys_get_temp_dir().'/analytics_webhook';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->logger->error('Analytics webhook notifier could not create spool directory.', [
                'endpoint' => $endpoint,
                'directory' => $dir,
            ]);

            return false;
        }

        try {
            $body = json_encode([
                'endpoint' => $endpoint,
                'payload' => $payload,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (strlen($body) > self::MAX_PAYLOAD_BYTES) {
                $this->logger->warning('Analytics webhook notifier rejected an oversized payload.', [
                    'endpoint' => $endpoint,
                    'bytes' => strlen($body),
                    'max_bytes' => self::MAX_PAYLOAD_BYTES,
                ]);

                return false;
            }
        } catch (\JsonException $exception) {
            $this->logger->error('Analytics webhook notifier failed to encode payload.', [
                'endpoint' => $endpoint,
                'exception' => $exception,
            ]);

            return false;
        }

        $name = sprintf('%s/%s.json', $dir, hash('sha256', $endpoint.'|'.$body));
        if (false === file_put_contents($name, $body, LOCK_EX)) {
            $this->logger->error('Analytics webhook notifier failed to write spool file.', [
                'endpoint' => $endpoint,
                'path' => $name,
            ]);

            return false;
        }

        $this->logger->info('Analytics webhook notifier spooled payload.', [
            'endpoint' => $endpoint,
            'path' => $name,
            'bytes' => strlen($body),
        ]);

        return true;
    }
}
