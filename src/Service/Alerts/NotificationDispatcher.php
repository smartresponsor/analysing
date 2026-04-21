<?php

declare(strict_types=1);

namespace App\Analysing\Service\Alerts;

use App\Analysing\Entity\Alerts\AlertRule;
use App\Analysing\ServiceInterface\Alerts\NotificationDispatcherInterface;
use Psr\Log\LoggerInterface;

final readonly class NotificationDispatcher implements NotificationDispatcherInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function dispatch(AlertRule $rule, string $message): void
    {
        $normalizedMessage = trim($message);
        if ('' === $normalizedMessage) {
            throw new \InvalidArgumentException('Alert notification message must not be empty.');
        }

        foreach ($rule->getChannels() as $channel) {
            if (is_string($channel)) {
                $type = trim($channel);
                if ('' === $type) {
                    continue;
                }

                $this->logger->info('Analytics alert notification dispatched.', [
                    'rule' => $rule->getCode(),
                    'channel' => $type,
                    'message' => $normalizedMessage,
                ]);
                continue;
            }

            if (!is_array($channel)) {
                continue;
            }

            $type = isset($channel['type']) && is_string($channel['type']) ? trim($channel['type']) : '';
            if ('' === $type) {
                continue;
            }

            $this->logger->info('Analytics alert notification dispatched.', [
                'rule' => $rule->getCode(),
                'channel' => $type,
                'target' => isset($channel['target']) && is_scalar($channel['target']) ? (string) $channel['target'] : null,
                'message' => $normalizedMessage,
            ]);
        }
    }
}
