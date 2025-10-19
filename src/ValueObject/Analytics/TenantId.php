<?php

namespace SmartResponsor\Analytics\ValueObject\Analytics;

final class TenantId { public function __construct(private string $value) {} public function value(): string { return $this->value; } public function __toString(): string { return $this->value; }}