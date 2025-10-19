<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

use SmartResponsor\Analytics\ValueObject\Analytics\TenantId; interface TenantScopeInterface { public function filter(array $rows, TenantId $tenant): array; }