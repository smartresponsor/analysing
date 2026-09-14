<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsClickhouseClientInterface;
use App\Analysing\ServiceInterface\AnalyticsFlagInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsFlag implements AnalyticsFlagInterface
{
    public function __construct(
        private AnalyticsClickhouseClientInterface $client,
        private LoggerInterface $logger,
        private string $salt,
    ) {
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return array{flag_key:string,user_id:string,enabled:bool,bucket:int,reason:string,rollout:int}
     */
    public function evaluate(array $param): array
    {
        $flagKey = $this->normalizeRequiredString($param, 'flag_key');
        $userId = $this->normalizeRequiredString($param, 'user_id');
        $allow = $this->normalizeAllowList($param['allow'] ?? []);
        $rollout = $this->normalizeRollout($param['rollout'] ?? 0);

        if (in_array($userId, $allow, true)) {
            $this->logger->info('Analytics flag granted by allow-list.', [
                'flag_key' => $flagKey,
                'user_id' => $userId,
            ]);

            return [
                'flag_key' => $flagKey,
                'user_id' => $userId,
                'enabled' => true,
                'bucket' => 0,
                'reason' => 'allow',
                'rollout' => $rollout,
            ];
        }

        $bucket = abs(crc32($this->salt.'|'.$flagKey.'|'.$userId)) % 100;
        $enabled = $bucket < $rollout;

        $this->logger->info('Analytics flag evaluated by rollout.', [
            'flag_key' => $flagKey,
            'user_id' => $userId,
            'rollout' => $rollout,
            'bucket' => $bucket,
            'enabled' => $enabled,
        ]);

        return [
            'flag_key' => $flagKey,
            'user_id' => $userId,
            'enabled' => $enabled,
            'bucket' => $bucket,
            'reason' => 'rollout',
            'rollout' => $rollout,
        ];
    }

    public function expose(array $param): array
    {
        $userId = $this->normalizeRequiredString($param, 'user_id');
        $vendorId = $this->normalizeRequiredString($param, 'vendor_id');
        $flagKey = $this->normalizeRequiredString($param, 'flag_key');
        $enabled = $this->normalizeEnabled($param['enabled'] ?? null);

        $rows = [[
            'event_name' => 'flag_expose',
            'event_type' => 'expose',
            'user_id' => $userId,
            'session_id' => '',
            'vendor_id' => $vendorId,
            'source' => 'api',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'properties' => [
                'flag_key' => $flagKey,
                'enabled' => $enabled,
            ],
        ]];

        $this->client->insertJsonEachRow('event_raw', $rows);
        $this->logger->info('Analytics flag exposure recorded.', [
            'flag_key' => $flagKey,
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'enabled' => $enabled,
        ]);

        return [
            'accepted' => 1,
            'flag_key' => $flagKey,
            'enabled' => $enabled,
        ];
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeRequiredString(array $param, string $field): string
    {
        $raw = $param[$field] ?? '';
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ('' === $value) {
            $this->logger->warning('Analytics flag domain rejected an empty required field.', [
                'field' => $field,
            ]);
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function normalizeAllowList(mixed $allow): array
    {
        if (null === $allow || '' === $allow) {
            return [];
        }

        if (!is_array($allow)) {
            $this->logger->warning('Analytics flag domain rejected a non-array allow list.', [
                'allow_type' => get_debug_type($allow),
            ]);
            throw new \InvalidArgumentException('allow must be an array when provided.');
        }

        $normalized = [];
        foreach ($allow as $entry) {
            if (!is_scalar($entry) && null !== $entry) {
                $this->logger->warning('Analytics flag domain ignored a non-scalar allow-list entry.', [
                    'entry_type' => get_debug_type($entry),
                ]);
                continue;
            }

            $value = trim((string) $entry);
            if ('' === $value) {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeRollout(mixed $value): int
    {
        $rollout = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if (!is_int($rollout)) {
            $this->logger->warning('Analytics flag domain rejected an invalid rollout value.', [
                'value_type' => get_debug_type($value),
                'value' => $value,
            ]);
            throw new \InvalidArgumentException('rollout must be an integer between 0 and 100.');
        }

        return $rollout;
    }

    private function normalizeEnabled(mixed $value): bool
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_bool($normalized)) {
            $this->logger->warning('Analytics flag domain rejected an invalid boolean field.', [
                'field' => 'enabled',
                'value_type' => get_debug_type($value),
                'value' => $value,
            ]);
            throw new \InvalidArgumentException('enabled must be a boolean value.');
        }

        return $normalized;
    }
}
