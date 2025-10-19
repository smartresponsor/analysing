<?php

namespace SmartResponsor\Analytics\Controller\Analytics;
use SmartResponsor\Analytics\Service\Analytics\HealthService;

final class HealthController { public function __construct(private HealthService $svc) {} public function ping(): array { return $this->svc->status(); }}