<?php
declare(strict_types=1);

namespace App\Service\Alerts;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NotificationDispatcher
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?HttpClientInterface $httpClient = null
    ) {}

    public function send(string $message, array $opts = []): void
    {
        $this->logger->info('[ALERT] ' . $message, $opts);
        if (!empty($opts['webhook'])) {
            $this->sendWebhook($opts['webhook'], ['text' => $message, 'meta' => $opts]);
        }
        if (!empty($opts['slack_webhook'])) {
            $this->sendWebhook($opts['slack_webhook'], ['text' => $message]);
        }
    }

    private function sendWebhook(string $url, array $payload): void
    {
        if (!$this->httpClient) { $this->logger->warning('HttpClient not available for webhook: ' . $url); return; }
        try {
            $this->httpClient->request('POST', $url, ['json' => $payload]);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook send failed: ' . $e->getMessage());
        }
    }
}
