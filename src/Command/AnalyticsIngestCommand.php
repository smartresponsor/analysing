<?php

namespace SmartResponsor\Analytics\Command;
use SmartResponsor\Analytics\Service\Analytics\MetricIngestService;

final class AnalyticsIngestCommand { public function __construct(private MetricIngestService $svc) {} public function run(array $payloads): int { foreach($payloads as $row){$this->svc->ingest($row);} return count($this->svc->dumpBuffer()); }}