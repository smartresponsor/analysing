<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

use SmartResponsor\Analytics\ValueObject\Analytics\KpiId; interface KpiRegistryInterface { public function list(): array; public function has(KpiId $id): bool; }