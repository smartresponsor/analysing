<?php

namespace SmartResponsor\Analytics\ValueObject\Analytics;

final class Segment { public function __construct(private string $code) {} public function code(): string { return $this->code; }}