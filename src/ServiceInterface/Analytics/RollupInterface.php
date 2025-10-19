<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface RollupInterface { public function sum(array $rows, string $field): float|int; }