<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsClickhouseClientInterface;
use App\Analysing\ServiceInterface\AnalyticsExperimentInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsExperiment implements AnalyticsExperimentInterface
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
     * @return array{experiment_id:string,user_id:string,variant:string,bucket:int,reason:string,rollout:int}
     */
    public function assign(array $param): array
    {
        $experimentId = $this->normalizeRequiredString($param, 'experiment_id');
        $userId = $this->normalizeRequiredString($param, 'user_id');
        $rollout = $this->normalizeRollout($param['rollout'] ?? 50);

        $bucket = abs(crc32($this->salt.'|'.$experimentId.'|'.$userId)) % 100;
        $variant = $bucket < $rollout ? 'treatment' : 'control';

        $this->logger->info('Analytics experiment allocated subject.', [
            'experiment_id' => $experimentId,
            'user_id' => $userId,
            'reason' => 'rollout',
            'rollout' => $rollout,
            'bucket' => $bucket,
            'variant' => $variant,
        ]);

        return [
            'experiment_id' => $experimentId,
            'user_id' => $userId,
            'variant' => $variant,
            'bucket' => $bucket,
            'reason' => 'rollout',
            'rollout' => $rollout,
        ];
    }

    public function expose(array $param): array
    {
        $userId = $this->normalizeRequiredString($param, 'user_id');
        $vendorId = $this->normalizeRequiredString($param, 'vendor_id');
        $experimentId = $this->normalizeRequiredString($param, 'experiment_id');
        $variant = $this->normalizeVariant($param['variant'] ?? null);

        $rows = [[
            'event_name' => 'experiment_expose',
            'event_type' => 'expose',
            'user_id' => $userId,
            'session_id' => '',
            'vendor_id' => $vendorId,
            'source' => 'api',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'properties' => [
                'experiment_id' => $experimentId,
                'variant' => $variant,
            ],
        ]];

        $this->client->insertJsonEachRow('event_raw', $rows);
        $this->logger->info('Analytics experiment exposure recorded.', [
            'experiment_id' => $experimentId,
            'user_id' => $userId,
            'vendor_id' => $vendorId,
            'variant' => $variant,
        ]);

        return [
            'accepted' => 1,
            'experiment_id' => $experimentId,
            'variant' => $variant,
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
            $this->logger->warning('Analytics experiment domain rejected an empty required field.', [
                'field' => $field,
            ]);
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $value;
    }

    private function normalizeRollout(mixed $value): int
    {
        $rollout = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if (!is_int($rollout)) {
            $this->logger->warning('Analytics experiment domain rejected an invalid rollout value.', [
                'value_type' => get_debug_type($value),
                'value' => $value,
            ]);
            throw new \InvalidArgumentException('rollout must be an integer between 0 and 100.');
        }

        return $rollout;
    }

    private function normalizeVariant(mixed $value): string
    {
        $variant = is_string($value) || is_int($value) || is_float($value) || is_bool($value) || null === $value ? trim((string) $value) : '';
        if ('' === $variant) {
            $this->logger->warning('Analytics experiment domain rejected an empty variant.');
            throw new \InvalidArgumentException('variant must be a non-empty string.');
        }

        return $variant;
    }
}
