<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\CacheInterface;

final class LocalCache implements CacheInterface { private array $data=[]; public function get(string $key, callable $fallback, int $ttl=60): mixed { $now=time(); $hit=$this->data[$key]??null; if($hit && $hit['exp']>$now) return $hit['v']; $value=$fallback(); $this->data[$key]=['v'=>$value,'exp'=>$now+$ttl]; return $value; } public function clear(): void { $this->data=[]; }}