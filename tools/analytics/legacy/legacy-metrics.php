<?php declare(strict_types=1);
namespace SmartResponsor\Metrics;

final class MetricRegistry
{
    /** @var array<string,int|float> */
    private array $gauge = [];
    /** @var array<string,int> */
    private array $counter = [];
    /** @var array<string,array{buckets:array<int,int>, sum:float, count:int}> */
    private array $hist = [];

    public function counterInc(string $name, array $labels = [], int $v = 1): void
    {
        $key = $this->key($name, $labels);
        $this->counter[$key] = ($this->counter[$key] ?? 0) + $v;
    }

    public function gaugeSet(string $name, array $labels = [], float $v = 0.0): void
    {
        $key = $this->key($name, $labels);
        $this->gauge[$key] = $v;
    }

    /** @param int[] $buckets milliseconds */
    public function histObserveMs(string $name, array $labels, int $ms, array $buckets = [50,100,250,500,1000,2000,5000]): void
    {
        $key = $this->key($name, $labels);
        if (!isset($this->hist[$key])) {
            $this->hist[$key] = ['buckets'=>array_fill_keys($buckets, 0), 'sum'=>0.0, 'count'=>0];
        }
        foreach ($this->hist[$key]['buckets'] as $le => $cnt) {
            if ($ms <= $le) $this->hist[$key]['buckets'][$le]++;
        }
        $this->hist[$key]['sum'] += $ms;
        $this->hist[$key]['count']++;
    }

    public function renderProm(): string
    {
        $out = [];
        // counters
        foreach ($this->counter as $k => $v) {
            [$name, $lbl] = $this->splitKey($k);
            $out[] = "# TYPE {$name} counter";
            $out[] = sprintf('%s%s %d', $name, $lbl, $v);
        }
        // gauges
        foreach ($this->gauge as $k => $v) {
            [$name, $lbl] = $this->splitKey($k);
            $out[] = "# TYPE {$name} gauge";
            $out[] = sprintf('%s%s %s', $name, $lbl, number_format((float)$v, 6, '.', ''));
        }
        // histograms
        foreach ($this->hist as $k => $h) {
            [$name, $lbl] = $this->splitKey($k);
            $out[] = "# TYPE {$name} histogram";
            $sum = number_format((float)$h['sum'], 6, '.', '');
            $count = $h['count'];
            foreach ($h['buckets'] as $le => $cnt) {
                $out[] = sprintf('%s_bucket%s,le="%d"} %d', $name, $this->mergeLabel($lbl), $le, $cnt);
            }
            $out[] = sprintf('%s_count%s %d', $name, $lbl, $count);
            $out[] = sprintf('%s_sum%s %s', $name, $lbl, $sum);
        }
        return implode("\n", $out) . "\n";
    }

    public function time(callable $fn, string $metricName, array $labels = []): mixed
    {
        $t0 = (int)floor(microtime(true)*1000);
        try {
            return $fn();
        } finally {
            $ms = (int)floor(microtime(true)*1000) - $t0;
            $this->histObserveMs($metricName, $labels, $ms);
        }
    }

    private function key(string $name, array $labels): string
    {
        if (!$labels) return $name;
        ksort($labels);
        $pairs = [];
        foreach ($labels as $k=>$v) { $pairs[] = $k.'="'.str_replace('"','\"',(string)$v).'"'; }
        return $name . '{' . implode(',', $pairs) . '}';
    }

    private function splitKey(string $key): array
    {
        $p = strpos($key, '{');
        if ($p === false) return [$key, ''];
        return [substr($key, 0, $p), substr($key, $p)];
    }

    private function mergeLabel(string $lbl): string
    {
        if ($lbl === '') return '{';
        return rtrim($lbl, '}') . ',';
    }
}
