<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\KpiRegistryInterface;
use App\ValueObject\Analytics\KpiId;
use Psr\Log\LoggerInterface;

final class KpiRegistry implements KpiRegistryInterface
{
    private const MAX_KPI_ENTRIES = 256;
    private const MAX_ID_LENGTH = 128;
    private const MAX_LABEL_LENGTH = 255;
    private const MAX_LOOKUP_LOG_SAMPLES = 16;
    private const KPI_ID_PATTERN = '/^[a-z0-9_]+$/';

    /** @var array<string, string> */
    private array $map;

    /** @var array<string, true> */
    private array $lookupMisses = [];

    public function __construct(
        array $map = [],
        private readonly LoggerInterface $logger,
    ) {
        $source = [] !== $map ? $map : $this->defaultMap();
        $normalized = $this->normalizeMap($source);

        if ([] === $normalized) {
            $this->logger->warning('Analytics KPI registry source normalized to an empty map; falling back to defaults.');
            $normalized = $this->normalizeMap($this->defaultMap());
        }

        $this->map = $normalized;
    }

    public function list(): array
    {
        $listed = $this->map;
        ksort($listed);

        try {
            $checksum = hash('sha256', json_encode($listed, JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            $checksum = null;
        }

        $this->logger->info('Analytics KPI registry listed metrics.', [
            'count' => count($listed),
            'checksum' => $checksum,
        ]);

        return $listed;
    }

    public function has(KpiId $id): bool
    {
        $normalizedId = trim((string) $id);
        if ('' === $normalizedId) {
            $this->logger->warning('Analytics KPI registry received an empty KPI id lookup.');

            return false;
        }

        $exists = isset($this->map[$normalizedId]);
        if (!$exists && !isset($this->lookupMisses[$normalizedId])) {
            if (count($this->lookupMisses) >= self::MAX_LOOKUP_LOG_SAMPLES) {
                array_shift($this->lookupMisses);
            }
            $this->lookupMisses[$normalizedId] = true;
            $this->logger->info('Analytics KPI registry lookup missed.', [
                'id' => $normalizedId,
            ]);
        }

        return $exists;
    }

    /** @return array<string,string> */
    private function defaultMap(): array
    {
        return [
            'orders_per_day' => 'Orders created per day',
            'revenue_total' => 'Total revenue recognized',
            'alerts_open' => 'Open alerts count',
        ];
    }

    /**
     * @param array<array-key,mixed> $source
     *
     * @return array<string,string>
     */
    private function normalizeMap(array $source): array
    {
        $normalized = [];
        if (count($source) > self::MAX_KPI_ENTRIES) {
            $this->logger->warning('Analytics KPI registry source exceeds the maximum number of entries; truncating.', [
                'count' => count($source),
                'limit' => self::MAX_KPI_ENTRIES,
            ]);
            $source = array_slice($source, 0, self::MAX_KPI_ENTRIES, true);
        }

        foreach ($source as $key => $label) {
            $id = trim((string) $key);
            $title = trim((string) $label);
            if ('' === $id || '' === $title) {
                $this->logger->warning('Analytics KPI registry skipped invalid entry.', [
                    'key' => $key,
                    'label' => $label,
                ]);
                continue;
            }

            if (strlen($id) > self::MAX_ID_LENGTH) {
                $this->logger->warning('Analytics KPI registry skipped an entry with an oversized id.', [
                    'id' => $id,
                    'max_length' => self::MAX_ID_LENGTH,
                ]);
                continue;
            }

            if (1 !== preg_match(self::KPI_ID_PATTERN, $id)) {
                $this->logger->warning('Analytics KPI registry skipped an entry with an invalid id pattern.', [
                    'id' => $id,
                    'pattern' => self::KPI_ID_PATTERN,
                ]);
                continue;
            }

            if (strlen($title) > self::MAX_LABEL_LENGTH) {
                $this->logger->warning('Analytics KPI registry truncated an oversized label.', [
                    'id' => $id,
                    'max_length' => self::MAX_LABEL_LENGTH,
                ]);
                $title = mb_substr($title, 0, self::MAX_LABEL_LENGTH);
            }

            if (isset($normalized[$id])) {
                $this->logger->warning('Analytics KPI registry replaced duplicate entry.', [
                    'id' => $id,
                ]);
            }

            $normalized[$id] = $title;
        }

        return $normalized;
    }
}
