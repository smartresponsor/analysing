<?php

namespace SmartResponsor\Analytics\Command;
use SmartResponsor\Analytics\Service\Analytics\RetentionService;

final class AnalyticsRetentionCommand { public function __construct(private RetentionService $svc) {} public function run(array $rows, int $days): array { return $this->svc->prune($rows,$days);} }