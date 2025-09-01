<?php
declare(strict_types=1);

namespace App\Service\Alerts;

use Psr\Log\LoggerInterface;

final class NotificationDispatcher
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @param array{channels?:array,email?:string,webhook?:string,slack?:string} $opts
     */
    public function send(string $message, array $opts = []): void
    {
        // Stub: log only. Integrate mailer/webhook/Slack in real env.
        $this->logger->info('[ALERT] ' . $message, $opts);
    }
}
