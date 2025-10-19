<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface MetricIngestInterface { public function ingest(array $payload): void; }