<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Domain\Analytics;

use App\Analysing\DomainInterface\Analytics\ClickhouseClientInterface;
use App\Analysing\DomainInterface\Analytics\InsightInterface;
use Psr\Log\LoggerInterface;

final readonly class Insight implements InsightInterface
{
    public function __construct(
        private ClickhouseClientInterface $client,
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
        $tenant = $this->normalizeNonEmptyString($param, 'tenant_id');
        $name = $this->normalizeNonEmptyString($param, 'event_name');
        $days = $this->normalizeDays($param);

        $rows = $this->validateSeriesRows($this->client->query(
            $this->loadQuery('anomaly/series.sql'),
            ['tenant_id' => $tenant, 'event_name' => $name, 'days' => $days],
        ));
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

        $this->logger->info('Insight anomaly detection completed.', [
            'tenant_id' => $tenant,
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
            $this->logger->error('Metric tree catalog is invalid JSON.', ['path' => __DIR__.'/../../metric_tree/catalog.json', 'exception' => $exception]);
            throw new \RuntimeException('Metric tree catalog is invalid.', 0, $exception);
        }

        if (!is_array($tree) || !isset($tree['trees']) || !is_array($tree['trees'])) {
            $this->logger->error('Metric tree catalog is invalid JSON.', ['path' => __DIR__.'/../../metric_tree/catalog.json']);
            throw new \RuntimeException('Metric tree catalog is invalid.');
        }

        $name = $this->normalizeNonEmptyString($param, 'name');
        $tenant = $this->normalizeNonEmptyString($param, 'tenant_id');
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

            $nodeId = $this->normalizeNodeString($node['id'] ?? null, 'node id', $name);
            $title = $this->normalizeNodeString($node['title'] ?? null, 'node title', $name);
            $sqlFile = $this->normalizeNodeString($node['sql'] ?? null, 'node sql file', $name);
            if ('' === $nodeId || '' === $title || '' === $sqlFile) {
                throw new \RuntimeException(sprintf('Metric tree %s contains an incomplete node definition.', $name));
            }

            $sqlPath = 'metric_tree/'.$sqlFile;
            $res = $this->client->query($this->loadQuery($sqlPath), ['tenant_id' => $tenant]);
            $val = $this->extractMetricTreeValue($res, $name, $nodeId);
            $nodes[] = ['id' => $nodeId, 'title' => $title, 'value' => $val];
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

        $this->logger->info('Insight metric tree computed.', [
            'tenant_id' => $tenant,
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
                $this->logger->error('Insight anomaly query returned an invalid row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException('Insight anomaly query returned an invalid row.');
            }

            $count = filter_var($row['user_count'], FILTER_VALIDATE_INT);
            if (!is_int($count)) {
                $this->logger->error('Insight anomaly query returned a non-integer user_count.', [
                    'row_index' => $index,
                    'user_count' => $row['user_count'],
                ]);
                throw new \RuntimeException('Insight anomaly query returned an invalid user_count.');
            }

            $validated[] = ['user_count' => $count];
        }

        return $validated;
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function extractMetricTreeValue(array $rows, string $treeName, string $nodeId): int
    {
        $row = $rows[0] ?? null;
        if (!is_array($row) || !array_key_exists('value', $row)) {
            $this->logger->error('Insight metric tree query returned an invalid value row.', [
                'tree' => $treeName,
                'node' => $nodeId,
            ]);
            throw new \RuntimeException(sprintf('Metric tree %s returned an invalid value row for node %s.', $treeName, $nodeId));
        }

        $value = filter_var($row['value'], FILTER_VALIDATE_INT);
        if (!is_int($value)) {
            $this->logger->error('Insight metric tree query returned a non-integer value.', [
                'tree' => $treeName,
                'node' => $nodeId,
                'value' => $row['value'],
            ]);
            throw new \RuntimeException(sprintf('Metric tree %s returned an invalid value for node %s.', $treeName, $nodeId));
        }

        return $value;
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

    private function loadQuery(string $relativePath): string
    {
        $path = __DIR__.'/../../queries/'.$relativePath;
        if (!is_file($path)) {
            $this->logger->error('Insight query file is missing.', ['path' => $path]);
            throw new \RuntimeException('Insight query file is missing: '.$relativePath);
        }

        $sql = file_get_contents($path);
        if (false === $sql || '' === trim($sql)) {
            $this->logger->error('Insight query file is unreadable or empty.', ['path' => $path]);
            throw new \RuntimeException('Insight query file is unreadable or empty: '.$relativePath);
        }

        return $sql;
    }

    private function loadMetricTreeCatalog(): string
    {
        $path = __DIR__.'/../../metric_tree/catalog.json';
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
