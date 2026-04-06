<?php

declare(strict_types=1);

namespace App\Service\Alerts;

use App\Entity\Alerts\AlertRule;
use App\ServiceInterface\Alerts\NotificationDispatcherInterface;
use Psr\Log\LoggerInterface;

final class NotificationDispatcher implements NotificationDispatcherInterface
{
    private const int MAX_MESSAGE_LENGTH = 2000;
    private const int MAX_CHANNELS = 32;
    private const int MAX_TARGET_LENGTH = 512;
    private const array ALLOWED_CHANNEL_TYPES = ['email', 'webhook', 'slack', 'log'];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function dispatch(AlertRule $rule, string $message): void
    {
        $startedAt = microtime(true);
        $message = trim($message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Alert dispatch message cannot be empty.');
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new \InvalidArgumentException('Alert dispatch message exceeds the maximum supported length.');
        }

        $channels = $this->normalizeChannels($rule);
        if ([] === $channels) {
            $this->logger->warning('Analytics alert matched but no dispatch channels are configured.', [
                'rule' => $rule->getCode(),
                'message' => $message,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return;
        }

        foreach ($channels as $channel) {
            $this->logger->warning('Analytics alert matched.', [
                'rule' => $rule->getCode(),
                'channel' => $channel['type'],
                'target' => $channel['target'],
                'message' => $message,
            ]);
        }

        $this->logger->info('Analytics alert dispatch completed.', [
            'rule' => $rule->getCode(),
            'channels' => count($channels),
            'duration_ms' => $this->durationMs($startedAt),
        ]);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /**
     * @return list<array{type:string,'target':?string}>
     */
    private function normalizeChannels(AlertRule $rule): array
    {
        $normalized = [];

        $sourceChannels = $rule->getChannels();
        if (count($sourceChannels) > self::MAX_CHANNELS) {
            $this->logger->warning('Analytics alert dispatch truncated channel list to the maximum supported size.', [
                'rule' => $rule->getCode(),
                'channels' => count($sourceChannels),
                'max_channels' => self::MAX_CHANNELS,
            ]);
            $sourceChannels = array_slice($sourceChannels, 0, self::MAX_CHANNELS);
        }

        foreach ($sourceChannels as $channel) {
            $type = '';
            $target = null;

            if (is_array($channel)) {
                $typeValue = $channel['type'] ?? '';
                $type = is_scalar($typeValue) ? trim((string) $typeValue) : '';
                $targetValue = $channel['target'] ?? null;
                $target = is_scalar($targetValue) ? trim((string) $targetValue) : null;
                if ('' === $target) {
                    $target = null;
                }
                if (null !== $target && mb_strlen($target) > self::MAX_TARGET_LENGTH) {
                    $this->logger->warning('Analytics alert dispatch ignored an overlong target.', [
                        'rule' => $rule->getCode(),
                        'channel_type' => $type,
                        'max_target_length' => self::MAX_TARGET_LENGTH,
                    ]);
                    $target = null;
                }
            } else {
                $type = is_scalar($channel) ? trim((string) $channel) : '';
            }

            if ('' === $type) {
                $this->logger->warning('Analytics alert dispatch ignored invalid channel entry.', [
                    'rule' => $rule->getCode(),
                    'channel' => $channel,
                ]);
                continue;
            }

            if (!in_array($type, self::ALLOWED_CHANNEL_TYPES, true)) {
                $this->logger->warning('Analytics alert dispatch ignored unsupported channel type.', [
                    'rule' => $rule->getCode(),
                    'channel_type' => $type,
                ]);
                continue;
            }

            $key = $type.'|'.($target ?? '');
            $normalized[$key] = [
                'type' => $type,
                'target' => $target,
            ];
        }

        return array_values($normalized);
    }
}
