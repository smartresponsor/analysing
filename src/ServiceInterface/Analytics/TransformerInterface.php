<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface TransformerInterface { public function map(array $rows, callable $fn): array; }