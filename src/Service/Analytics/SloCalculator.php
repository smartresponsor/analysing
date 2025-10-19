<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\SloCalculatorInterface;

final class SloCalculator implements SloCalculatorInterface { public function availability(array $values): float { if(count($values)===0) return 0.0; $ok=count(array_filter($values, fn($v)=> $v>=1.0)); return $ok / count($values); }}