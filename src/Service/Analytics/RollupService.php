<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\RollupInterface;

final class RollupService implements RollupInterface { public function sum(array $rows, string $field): float|int { $total=0; foreach($rows as $r){ $total += (float)($r[$field]??0);} return $total; }}