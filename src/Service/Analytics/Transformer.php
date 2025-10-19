<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\TransformerInterface;

final class Transformer implements TransformerInterface { public function map(array $rows, callable $fn): array { return array_map($fn,$rows); }}