<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\MetricIngestServiceInterface;
use Psr\Log\LoggerInterface;

final class MetricIngestService implements MetricIngestServiceInterface
{
    private const int MAX_BUFFER_SIZE = 1000;
    private const int MAX_FIELDS = 128;
    private const int MAX_NESTED_ITEMS = 128;
    private const int MAX_KEY_LENGTH = 128;
    private const int MAX_STRING_LENGTH = 2048;

    /** @var list<array<string,mixed>> */
    private array $buffer = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function ingest(array $payload): void
    {
        $normalizedPayload = $this->normalizePayload($payload);
        $normalizedPayload['ingested_at'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        try {
            $normalizedPayload['payload_checksum'] = hash('sha256', json_encode($normalizedPayload, JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Metric ingest payload checksum could not be calculated.', 0, $exception);
        }
        $this->buffer[] = $normalizedPayload;
        $this->trimBufferIfNeeded();

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
        if ([] === $payload) {
            $this->logger->warning('Analytics metric ingest rejected because payload is empty.');
            throw new \InvalidArgumentException('Metric ingest payload must not be empty.');
        }

        if (count($payload) > self::MAX_FIELDS) {
            $this->logger->warning('Analytics metric ingest rejected because payload contains too many fields.', [
                'fields' => count($payload),
                'max_fields' => self::MAX_FIELDS,
            ]);
            throw new \InvalidArgumentException('Metric ingest payload exceeds the maximum number of fields.');
        }

        $normalized = [];
        foreach ($payload as $key => $value) {
            $normalizedKey = trim($key);
            if (strlen($normalizedKey) > self::MAX_KEY_LENGTH) {
                $this->logger->warning('Analytics metric ingest rejected because payload contains an overlong key.', [
                    'key' => $normalizedKey,
                ]);
                throw new \InvalidArgumentException('Metric ingest keys must not exceed 128 characters.');
            }
            if ('' === $normalizedKey) {
                $this->logger->warning('Analytics metric ingest rejected because payload contains an empty key.');
                throw new \InvalidArgumentException('Metric ingest payload must not contain empty keys.');
            }

            if (is_scalar($value) || null === $value) {
                $normalized[$normalizedKey] = $this->normalizeScalarValue($value, $normalizedKey);
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
            throw new \InvalidArgumentException(sprintf('Metric ingest field %s contains an unsupported value type.', $normalizedKey));
        }

        return $normalized;
    }

    /**
     * @param array<array-key,mixed> $value
     *
     * @return array<array-key,scalar|null>
     */
    private function normalizeNestedArray(array $value, string $field): array
    {
        if ([] === $value) {
            $this->logger->warning('Analytics metric ingest rejected because payload contains an empty array field.', [
                'field' => $field,
            ]);
            throw new \InvalidArgumentException(sprintf('Metric ingest field %s must not be an empty array.', $field));
        }

        if (count($value) > self::MAX_NESTED_ITEMS) {
            $this->logger->warning('Analytics metric ingest rejected because an array field contains too many items.', [
                'field' => $field,
                'items' => count($value),
                'max_items' => self::MAX_NESTED_ITEMS,
            ]);
            throw new \InvalidArgumentException(sprintf('Metric ingest field %s exceeds the maximum number of items.', $field));
        }

        $normalized = [];
        foreach ($value as $itemKey => $itemValue) {
            if (!is_scalar($itemValue) && null !== $itemValue) {
                $this->logger->warning('Analytics metric ingest rejected because an array field contains a non-scalar item.', [
                    'field' => $field,
                    'item_key' => $itemKey,
                    'item_type' => get_debug_type($itemValue),
                ]);
                throw new \InvalidArgumentException(sprintf('Metric ingest field %s must contain only scalar values.', $field));
            }

            $normalized[$itemKey] = $this->normalizeScalarValue($itemValue, $field);
        }

        return $normalized;
    }

    private function normalizeScalarValue(bool|int|float|string|null $value, string $field): bool|int|float|string|null
    {
        if (is_string($value) && strlen($value) > self::MAX_STRING_LENGTH) {
            $this->logger->warning('Analytics metric ingest rejected because payload contains an overlong scalar value.', [
                'field' => $field,
                'length' => strlen($value),
                'max_length' => self::MAX_STRING_LENGTH,
            ]);
            throw new \InvalidArgumentException(sprintf('Metric ingest field %s exceeds the maximum allowed length.', $field));
        }

        return $value;
    }

    private function trimBufferIfNeeded(): void
    {
        while (count($this->buffer) > self::MAX_BUFFER_SIZE) {
            array_shift($this->buffer);
            $this->logger->warning('Analytics metric ingest evicted the oldest buffered payload because the buffer reached its maximum size.', [
                'max_buffer_size' => self::MAX_BUFFER_SIZE,
            ]);
        }
    }
}
