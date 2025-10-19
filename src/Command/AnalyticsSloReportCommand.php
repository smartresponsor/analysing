<?php

namespace SmartResponsor\Analytics\Command;
use SmartResponsor\Analytics\Service\Analytics\SloCalculator;

final class AnalyticsSloReportCommand { public function __construct(private SloCalculator $svc) {} public function run(array $values): float { return $this->svc->availability($values); }}