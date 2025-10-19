<?php

namespace SmartResponsor\Analytics\ValueObject\Analytics;

final class Dimension { public function __construct(private string $name) {} public function name(): string { return $this->name; }}