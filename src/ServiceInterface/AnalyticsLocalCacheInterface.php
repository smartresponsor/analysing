<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsLocalCacheInterface extends AnalyticsCacheInterface
{
    public function get(string $key, callable $fallback, int $ttl = 60): mixed;

    public function clear(): void;
}
