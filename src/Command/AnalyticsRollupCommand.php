<?php

namespace SmartResponsor\Analytics\Command;
use SmartResponsor\Analytics\Service\Analytics\RollupService;

final class AnalyticsRollupCommand { public function __construct(private RollupService $svc) {} public function run(array $rows, string $field): float|int { return $this->svc->sum($rows,$field);} }