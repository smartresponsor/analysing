<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\MetricIngestInterface;

final class MetricIngestService implements MetricIngestInterface { private array $buffer=[]; public function ingest(array $payload): void { $this->buffer[]=$payload; } public function dumpBuffer(): array { return $this->buffer; }}