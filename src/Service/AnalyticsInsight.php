<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface;
use App\Analysing\ServiceInterface\AnalyticsInsightInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsInsight implements AnalyticsInsightInterface
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return array<string,mixed>
     */
    public function detectAnomaly(array $param): array
    {
        $vendor = $this->normalizeNonEmptyString($param, 'vendor_id');
        $name = $this->normalizeNonEmptyString($param, 'event_name');
        $days = $this->normalizeDays($param);

        $rows = $this->validateSeriesRows($this->repository->fetchAnomalySeries($name, $days));
        $series = array_map(static fn (array $row): int => (int) $row['user_count'], $rows);
        if (count($series) < 7) {
            return ['anomaly' => false, 'reason' => 'insufficient-data', 'series' => $rows];
        }

        $latest = $series[array_key_last($series)];
        $vals = $series;
        sort($vals);
        $n = count($vals);
        $median = $n % 2 ? $vals[intdiv($n, 2)] : 0.5 * ($vals[intdiv($n, 2) - 1] + $vals[intdiv($n, 2)]);
        $dev = [];
        foreach ($vals as $x) {
            $dev[] = abs($x - $median);
        }
        sort($dev);
        $mad = $n % 2 ? $dev[intdiv($n, 2)] : 0.5 * ($dev[intdiv($n, 2) - 1] + $dev[intdiv($n, 2)]);
        $score = $mad > 0 ? abs($latest - $median) / (1.4826 * $mad) : 0.0;

        $result = ['anomaly' => $score >= 3.5, 'score' => $score, 'median' => $median, 'mad' => $mad, 'series' => $rows];

        $this->logger->info('AnalyticsInsight anomaly detection completed.', [
            'vendor_id' => $vendor,
            'event_name' => $name,
            'days' => $days,
            'points' => count($rows),
            'anomaly' => $result['anomaly'],
        ]);

        return $result;
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return array{name:string,nodes:list<array<string,mixed>>,edges:list<array{from:string,to:string}>}
     */
    public function computeMetricTree(array $param): array
    {
        $treeJson = $this->loadMetricTreeCatalog();
        try {
            $tree = json_decode($treeJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Metric tree catalog is invalid JSON.', ['path' => __DIR__.'/../metric_tree/catalog.json', 'exception' => $exception]);
            throw new \RuntimeException('Metric tree catalog is invalid.', 0, $exception);
        }

        if (!is_array($tree) || !isset($tree['trees']) || !is_array($tree['trees'])) {
            $this->logger->error('Metric tree catalog is invalid JSON.', ['path' => __DIR__.'/../metric_tree/catalog.json']);
            throw new \RuntimeException('Metric tree catalog is invalid.');
        }

        $name = $this->normalizeNonEmptyString($param, 'name');
        $vendor = $this->normalizeNonEmptyString($param, 'vendor_id');
        $conf = $tree['trees'][$name] ?? null;
        if (!is_array($conf)) {
            throw new \InvalidArgumentException(sprintf('Unknown metric tree: %s', $name));
        }

        if (!isset($conf['nodes']) || !is_array($conf['nodes'])) {
            throw new \RuntimeException(sprintf('Metric tree %s has invalid nodes definition.', $name));
        }

        if (!isset($conf['edges']) || !is_array($conf['edges'])) {
            throw new \RuntimeException(sprintf('Metric tree %s has invalid edges definition.', $name));
        }

        $nodes = [];
        foreach ($conf['nodes'] as $node) {
            if (!is_array($node)) {
                throw new \RuntimeException(sprintf('Metric tree %s contains an invalid node definition.', $name));
            }

            /** @var array<string,mixed> $node */
            $nodeId = $this->normalizeNodeString($node['id'] ?? null, 'node id', $name);
            $title = $this->normalizeNodeString($node['title'] ?? null, 'node title', $name);
            $metric = $this->resolveMetricName($node, $nodeId, $name);
            if ('' === $nodeId || '' === $title || '' === $metric) {
                throw new \RuntimeException(sprintf('Metric tree %s contains an incomplete node definition.', $name));
            }

            $nodes[] = [
                'id' => $nodeId,
                'title' => $title,
                'value' => $this->repository->fetchLatestMetricValue($metric),
            ];
        }

        $edges = [];
        foreach ($conf['edges'] as $edge) {
            if (!is_array($edge)) {
                throw new \RuntimeException(sprintf('Metric tree %s contains an invalid edge definition.', $name));
            }

            $from = $this->normalizeNodeString($edge['from'] ?? null, 'edge from', $name);
            $to = $this->normalizeNodeString($edge['to'] ?? null, 'edge to', $name);
            if ('' === $from || '' === $to) {
                throw new \RuntimeException(sprintf('Metric tree %s contains an incomplete edge definition.', $name));
            }

            $edges[] = ['from' => $from, 'to' => $to];
        }

        $this->logger->info('AnalyticsInsight metric tree computed.', [
            'vendor_id' => $vendor,
            'name' => $name,
            'nodes' => count($nodes),
            'edges' => count($edges),
        ]);

        return ['name' => $name, 'nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{user_count:int}>
     */
    private function validateSeriesRows(array $rows): array
    {
        $validated = [];

        foreach ($rows as $index => $row) {
            if (!array_key_exists('user_count', $row)) {
                $this->logger->error('AnalyticsInsight anomaly query returned an invalid row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException('AnalyticsInsight anomaly query returned an invalid row.');
            }

            $count = filter_var($row['user_count'], FILTER_VALIDATE_INT);
            if (!is_int($count)) {
                $this->logger->error('AnalyticsInsight anomaly query returned a non-integer user_count.', [
                    'row_index' => $index,
                    'user_count' => $row['user_count'],
                ]);
                throw new \RuntimeException('AnalyticsInsight anomaly query returned an invalid user_count.');
            }

            $validated[] = ['user_count' => $count];
        }

        return $validated;
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeNonEmptyString(array $param, string $field): string
    {
        $raw = $param[$field] ?? '';
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ('' === $value) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeDays(array $param): int
    {
        if (!array_key_exists('days', $param)) {
            throw new \InvalidArgumentException('days must be provided.');
        }

        $value = filter_var($param['days'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!is_int($value)) {
            throw new \InvalidArgumentException('days must be a positive integer.');
        }

        return $value;
    }

    private function normalizeNodeString(mixed $value, string $field, string $treeName): string
    {
        if (!is_scalar($value)) {
            throw new \RuntimeException(sprintf('Metric tree %s contains an invalid %s definition.', $treeName, $field));
        }

        return trim((string) $value);
    }

    /**
     * @param array<string,mixed> $node
     */
    private function resolveMetricName(array $node, string $nodeId, string $treeName): string
    {
        if (isset($node['metric'])) {
            return $this->normalizeNodeString($node['metric'], 'node metric', $treeName);
        }

        if (!isset($node['sql'])) {
            return $nodeId;
        }

        $sqlFile = $this->normalizeNodeString($node['sql'], 'node sql file', $treeName);
        $metric = preg_replace('/\.sql$/', '', basename($sqlFile));
        if (!is_string($metric) || '' === trim($metric)) {
            throw new \RuntimeException(sprintf('Metric tree %s contains an invalid sql-derived metric name.', $treeName));
        }

        return 'north_star_root' === $metric ? 'north_star' : $metric;
    }

    private function loadMetricTreeCatalog(): string
    {
        $path = __DIR__.'/../metric_tree/catalog.json';
        if (!is_file($path)) {
            $this->logger->error('Metric tree catalog file is missing.', ['path' => $path]);
            throw new \RuntimeException('Metric tree catalog file is missing.');
        }

        $json = file_get_contents($path);
        if (false === $json || '' === trim($json)) {
            $this->logger->error('Metric tree catalog file is unreadable or empty.', ['path' => $path]);
            throw new \RuntimeException('Metric tree catalog file is unreadable or empty.');
        }

        return $json;
    }
}
