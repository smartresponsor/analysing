<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsWebhookNotifierInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsWebhookNotifier implements AnalyticsWebhookNotifierInterface
{
    private const int MAX_ENDPOINT_LENGTH = 1024;
    private const int MAX_PAYLOAD_BYTES = 1048576;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(string $endpoint, array $payload): bool
    {
        $endpoint = $this->normalizeEndpoint($endpoint);
        if (null === $endpoint) {
            return false;
        }

        $body = $this->encodePayload($endpoint, $payload);
        if (null === $body) {
            return false;
        }

        $directory = $this->prepareSpoolDirectory($endpoint);
        if (null === $directory) {
            return false;
        }

        return $this->writeSpoolFile($directory, $endpoint, $body);
    }

    private function normalizeEndpoint(string $endpoint): ?string
    {
        $endpoint = trim($endpoint);
        if ('' === $endpoint) {
            $this->logger->warning('Analytics webhook notifier rejected an empty endpoint.');

            return null;
        }

        $length = mb_strlen($endpoint);
        if ($length > self::MAX_ENDPOINT_LENGTH) {
            $this->logger->warning('Analytics webhook notifier rejected an overlong endpoint.', [
                'length' => $length,
                'max_length' => self::MAX_ENDPOINT_LENGTH,
            ]);

            return null;
        }

        return $endpoint;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodePayload(string $endpoint, array $payload): ?string
    {
        try {
            $body = json_encode([
                'endpoint' => $endpoint,
                'payload' => $payload,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Analytics webhook notifier failed to encode payload.', [
                'endpoint' => $endpoint,
                'exception' => $exception,
            ]);

            return null;
        }

        $bytes = strlen($body);
        if ($bytes > self::MAX_PAYLOAD_BYTES) {
            $this->logger->warning('Analytics webhook notifier rejected an oversized payload.', [
                'endpoint' => $endpoint,
                'bytes' => $bytes,
                'max_bytes' => self::MAX_PAYLOAD_BYTES,
            ]);

            return null;
        }

        return $body;
    }

    private function prepareSpoolDirectory(string $endpoint): ?string
    {
        $directory = sys_get_temp_dir().'/analytics_webhook';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            $this->logger->error('Analytics webhook notifier could not create spool directory.', [
                'endpoint' => $endpoint,
                'directory' => $directory,
            ]);

            return null;
        }

        return $directory;
    }

    private function writeSpoolFile(string $directory, string $endpoint, string $body): bool
    {
        $name = sprintf('%s/%s.json', $directory, hash('sha256', $endpoint.'|'.$body));
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
