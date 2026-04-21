<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface LocalCacheInterface extends CacheInterface
{
    public function get(string $key, callable $fallback, int $ttl = 60): mixed;

    public function clear(): void;
}
