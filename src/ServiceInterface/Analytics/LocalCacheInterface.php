<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface LocalCacheInterface extends CacheInterface
{
    public function get(string $key, callable $fallback, int $ttl = 60): mixed;

    public function clear(): void;
}
