<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface CacheInterface
{
    /**
     * @template TValue
     *
     * @param callable():TValue $fallback
     *
     * @return TValue
     */
    public function get(string $key, callable $fallback, int $ttl = 60): mixed;

    public function clear(): void;
}
