<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\AsyncQueryServiceInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class AsyncQueryService implements AsyncQueryServiceInterface
{
    /** @var array<string, array<string,mixed>> */
    private array $jobs = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function submit(array $query): string
    {
        $normalizedQuery = $this->normalizeQuery($query);

        $id = bin2hex(random_bytes(8));
        $this->jobs[$id] = [
            'id' => $id,
            'state' => 'done',
            'submitted_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'result' => ['rows' => [], 'query' => $normalizedQuery],
        ];

        $this->logger->info('Analytics async query submitted.', [
            'id' => $id,
            'keys' => array_keys($normalizedQuery),
        ]);

        return $id;
    }

    public function status(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            $this->logger->warning('Analytics async query status requested with an empty id.');
            throw new InvalidArgumentException('Async query id must not be empty.');
        }

        if (strlen($id) > 64) {
            $this->logger->warning('Analytics async query status requested with an overlong id.', ['id' => $id]);
            throw new InvalidArgumentException('Async query id is too long.');
        }

        if (!isset($this->jobs[$id])) {
            $this->logger->info('Analytics async query status requested for an unknown id.', ['id' => $id]);

            return [
                'id' => $id,
                'state' => 'not_found',
            ];
        }

        $job = $this->jobs[$id];
        $this->logger->info('Analytics async query status resolved.', [
            'id' => $id,
            'state' => $job['state'] ?? 'unknown',
        ]);

        return $job;
    }

    /**
     * @param array<string,mixed> $query
     *
     * @return array<string,scalar|array<array-key,scalar|null>|null>
     */
    private function normalizeQuery(array $query): array
    {
        if ($query === []) {
            $this->logger->warning('Analytics async query submission rejected because payload is empty.');
            throw new InvalidArgumentException('Async query payload must not be empty.');
        }

        $normalized = [];
        foreach ($query as $key => $value) {
            $normalizedKey = trim((string) $key);
            if (strlen($normalizedKey) > 128) {
                $this->logger->warning('Analytics async query submission rejected because a key is too long.', [
                    'key' => $normalizedKey,
                ]);
                throw new InvalidArgumentException('Async query payload keys must not exceed 128 characters.');
            }
            if ($normalizedKey === '') {
                $this->logger->warning('Analytics async query submission rejected because it contains an empty key.');
                throw new InvalidArgumentException('Async query payload must not contain empty keys.');
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$normalizedKey] = $value;
                continue;
            }

            if (!is_array($value)) {
                $this->logger->warning('Analytics async query submission rejected because it contains a non-array complex value.', [
                    'key' => $normalizedKey,
                    'value_type' => get_debug_type($value),
                ]);
                throw new InvalidArgumentException(sprintf('Async query field %s contains an unsupported value type.', $normalizedKey));
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
        $normalized = [];
        foreach ($value as $itemKey => $itemValue) {
            if (!is_scalar($itemValue) && $itemValue !== null) {
                $this->logger->warning('Analytics async query submission rejected because an array field contains a non-scalar item.', [
                    'field' => $field,
                    'item_key' => $itemKey,
                    'item_type' => get_debug_type($itemValue),
                ]);
                throw new InvalidArgumentException(sprintf('Async query field %s must contain only scalar values.', $field));
            }

            $normalized[$itemKey] = $itemValue;
        }

        if ($normalized === []) {
            $this->logger->warning('Analytics async query submission rejected because an array field is empty.', [
                'field' => $field,
            ]);
            throw new InvalidArgumentException(sprintf('Async query field %s must not be an empty array.', $field));
        }

        return $normalized;
    }
}
