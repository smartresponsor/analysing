<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\TenantScopeInterface;
use SmartResponsor\Analytics\ValueObject\Analytics\TenantId;

final class TenantScope implements TenantScopeInterface { public function filter(array $rows, TenantId $tenant): array { return array_values(array_filter($rows, fn($r)=>(string)($r['tenant']??'')===(string)$tenant)); }}