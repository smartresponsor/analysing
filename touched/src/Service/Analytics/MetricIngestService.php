<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\MetricIngestServiceInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class MetricIngestService implements MetricIngestServiceInterface
{
    /** @var list<array<string,mixed>> */
    private array $buffer = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function ingest(array $payload): void
    {
        $normalizedPayload = $this->normalizePayload($payload);
        $normalizedPayload['ingested_at'] = (new DateTimeImmutable())->format(DATE_ATOM);
        $this->buffer[] = $normalizedPayload;

        $this->logger->info('Analytics metric ingest buffered payload.', [
            'buffer_size' => count($this->buffer),
            'keys' => array_keys($normalizedPayload),
        ]);
    }

    public function dumpBuffer(): array
    {
        return $this->buffer;
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function normalizePayload(array $payload): array
    {
        if ($payload === []) {
            $this->logger->warning('Analytics metric ingest rejected because payload is empty.');
            throw new InvalidArgumentException('Metric ingest payload must not be empty.');
        }

        $normalized = [];
        foreach ($payload as $key => $value) {
            $normalizedKey = trim((string) $key);
            if (strlen($normalizedKey) > 128) {
                $this->logger->warning('Analytics metric ingest rejected because payload contains an overlong key.', [
                    'key' => $normalizedKey,
                ]);
                throw new InvalidArgumentException('Metric ingest keys must not exceed 128 characters.');
            }
            if ($normalizedKey === '') {
                $this->logger->warning('Analytics metric ingest rejected because payload contains an empty key.');
                throw new InvalidArgumentException('Metric ingest payload must not contain empty keys.');
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$normalizedKey] = $value;
                continue;
            }

            if (is_array($value)) {
                $normalized[$normalizedKey] = $this->normalizeNestedArray($value, $normalizedKey);
                continue;
            }

            $this->logger->warning('Analytics metric ingest rejected because payload contains an unsupported value type.', [
                'key' => $normalizedKey,
                'value_type' => get_debug_type($value),
            ]);
            throw new InvalidArgumentException(sprintf('Metric ingest field %s contains an unsupported value type.', $normalizedKey));
        }

        return $normalized;
    }

    /**
     * @param array<array-key,mixed> $value
     * @return array<array-key,scalar|null>
     */
    private function normalizeNestedArray(array $value, string $field): array
    {
        if ($value === []) {
            $this->logger->warning('Analytics metric ingest rejected because payload contains an empty array field.', [
                'field' => $field,
            ]);
            throw new InvalidArgumentException(sprintf('Metric ingest field %s must not be an empty array.', $field));
        }

        $normalized = [];
        foreach ($value as $itemKey => $itemValue) {
            if (!is_scalar($itemValue) && $itemValue !== null) {
                $this->logger->warning('Analytics metric ingest rejected because an array field contains a non-scalar item.', [
                    'field' => $field,
                    'item_key' => $itemKey,
                    'item_type' => get_debug_type($itemValue),
                ]);
                throw new InvalidArgumentException(sprintf('Metric ingest field %s must contain only scalar values.', $field));
            }

            $normalized[$itemKey] = $itemValue;
        }

        return $normalized;
    }

}
