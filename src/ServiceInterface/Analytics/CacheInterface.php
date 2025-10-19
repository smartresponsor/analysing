<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface CacheInterface { public function get(string $key, callable $fallback, int $ttl=60): mixed; public function clear(): void; }