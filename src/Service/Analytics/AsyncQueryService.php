<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\AsyncQueryServiceInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;

final class AsyncQueryService implements AsyncQueryServiceInterface
{
    private const int MAX_JOBS = 256;
    private const int MAX_QUERY_FIELDS = 64;
    private const int MAX_ARRAY_ITEMS = 128;
    private const int MAX_KEY_LENGTH = 128;
    private const int MAX_SCALAR_STRING_LENGTH = 2048;

    /** @var array<string, array<string,mixed>> */
    private array $jobs = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function submit(array $query): string
    {
        $normalizedQuery = $this->normalizeQuery($query);

        $id = $this->newJobId();
        $submittedAt = gmdate(DATE_ATOM);
        try {
            $queryChecksum = hash('sha256', json_encode($normalizedQuery, JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Async query payload checksum could not be calculated.', 0, $exception);
        }
        $this->jobs[$id] = [
            'id' => $id,
            'state' => 'done',
            'submitted_at' => $submittedAt,
            'query_checksum' => $queryChecksum,
            'result' => ['rows' => [], 'query' => $normalizedQuery],
        ];
        $this->trimJobsIfNeeded();

        $this->logger->info('Analytics async query submitted.', [
            'id' => $id,
            'keys' => array_keys($normalizedQuery),
            'submitted_at' => $submittedAt,
            'query_checksum' => $queryChecksum,
        ]);

        return $id;
    }

    public function status(string $id): array
    {
        $id = trim($id);
        if ('' === $id) {
            $this->logger->warning('Analytics async query status requested with an empty id.');
            throw new \InvalidArgumentException('Async query id must not be empty.');
        }

        if (strlen($id) > 64) {
            $this->logger->warning('Analytics async query status requested with an overlong id.', ['id' => $id]);
            throw new \InvalidArgumentException('Async query id is too long.');
        }

        if (!isset($this->jobs[$id])) {
            $this->logger->info('Analytics async query status requested for an unknown id.', ['id' => $id]);

            return [
                'id' => $id,
                'state' => 'not_found',
            ];
        }

        $job = $this->jobs[$id];
        $submittedTs = isset($job['submitted_at']) && is_string($job['submitted_at']) ? strtotime($job['submitted_at']) : false;
        $job['age_ms'] = is_int($submittedTs) ? max(0, (time() - $submittedTs) * 1000) : null;
        $this->logger->info('Analytics async query status resolved.', [
            'id' => $id,
            'state' => $job['state'],
            'age_ms' => $job['age_ms'],
        ]);

        /** @var array{id:string,state:string,submitted_at?:string,result?:array<string,mixed>} $job */
        return $job;
    }

    /** @return non-empty-string */
    private function newJobId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (RandomException $exception) {
            $this->logger->warning('Analytics async query id generation fell back to deterministic entropy.', [
                'exception' => $exception,
            ]);

            return substr(hash('sha256', uniqid('analytics_async_', true)), 0, 16);
        }
    }

    /**
     * @param array<string,mixed> $query
     *
     * @return array<string,scalar|array<array-key,scalar|null>|null>
     */
    private function normalizeQuery(array $query): array
    {
        if ([] === $query) {
            $this->logger->warning('Analytics async query submission rejected because payload is empty.');
            throw new \InvalidArgumentException('Async query payload must not be empty.');
        }

        if (count($query) > self::MAX_QUERY_FIELDS) {
            $this->logger->warning('Analytics async query submission rejected because payload contains too many fields.', [
                'fields' => count($query),
                'max_fields' => self::MAX_QUERY_FIELDS,
            ]);
            throw new \InvalidArgumentException('Async query payload exceeds the maximum number of fields.');
        }

        $normalized = [];
        foreach ($query as $key => $value) {
            $normalizedKey = trim($key);
            if (strlen($normalizedKey) > self::MAX_KEY_LENGTH) {
                $this->logger->warning('Analytics async query submission rejected because a key is too long.', [
                    'key' => $normalizedKey,
                ]);
                throw new \InvalidArgumentException('Async query payload keys must not exceed 128 characters.');
            }
            if ('' === $normalizedKey) {
                $this->logger->warning('Analytics async query submission rejected because it contains an empty key.');
                throw new \InvalidArgumentException('Async query payload must not contain empty keys.');
            }

            if (is_scalar($value) || null === $value) {
                $normalized[$normalizedKey] = $this->normalizeScalarValue($value, $normalizedKey);
                continue;
            }

            if (!is_array($value)) {
                $this->logger->warning('Analytics async query submission rejected because it contains a non-array complex value.', [
                    'key' => $normalizedKey,
                    'value_type' => get_debug_type($value),
                ]);
                throw new \InvalidArgumentException(sprintf('Async query field %s contains an unsupported value type.', $normalizedKey));
            }

            $normalized[$normalizedKey] = $this->normalizeScalarList($value, $normalizedKey);
        }

        return $normalized;
    }

    /**
     * @param array<array-key,mixed> $value
     *
     * @return array<array-key,scalar|null>
     */
    private function normalizeScalarList(array $value, string $field): array
    {
        if (count($value) > self::MAX_ARRAY_ITEMS) {
            $this->logger->warning('Analytics async query submission rejected because an array field is too large.', [
                'field' => $field,
                'items' => count($value),
                'max_items' => self::MAX_ARRAY_ITEMS,
            ]);
            throw new \InvalidArgumentException(sprintf('Async query field %s exceeds the maximum number of items.', $field));
        }

        $normalized = [];
        foreach ($value as $itemKey => $itemValue) {
            if (!is_scalar($itemValue) && null !== $itemValue) {
                $this->logger->warning('Analytics async query submission rejected because an array field contains a non-scalar item.', [
                    'field' => $field,
                    'item_key' => $itemKey,
                    'item_type' => get_debug_type($itemValue),
                ]);
                throw new \InvalidArgumentException(sprintf('Async query field %s must contain only scalar values.', $field));
            }

            $normalized[$itemKey] = $this->normalizeScalarValue($itemValue, $field);
        }

        if ([] === $normalized) {
            $this->logger->warning('Analytics async query submission rejected because an array field is empty.', [
                'field' => $field,
            ]);
            throw new \InvalidArgumentException(sprintf('Async query field %s must not be an empty array.', $field));
        }

        return $normalized;
    }

    private function normalizeScalarValue(bool|int|float|string|null $value, string $field): bool|int|float|string|null
    {
        if (is_string($value) && strlen($value) > self::MAX_SCALAR_STRING_LENGTH) {
            $this->logger->warning('Analytics async query submission rejected because a scalar field is too long.', [
                'field' => $field,
                'length' => strlen($value),
                'max_length' => self::MAX_SCALAR_STRING_LENGTH,
            ]);
            throw new \InvalidArgumentException(sprintf('Async query field %s exceeds the maximum allowed length.', $field));
        }

        return $value;
    }

    private function trimJobsIfNeeded(): void
    {
        while (count($this->jobs) > self::MAX_JOBS) {
            $oldestId = array_key_first($this->jobs);
            if (!is_string($oldestId)) {
                break;
            }

            unset($this->jobs[$oldestId]);
            $this->logger->info('Analytics async query evicted the oldest job because the queue reached its maximum size.', [
                'evicted_id' => $oldestId,
                'max_jobs' => self::MAX_JOBS,
            ]);
        }
    }
}
