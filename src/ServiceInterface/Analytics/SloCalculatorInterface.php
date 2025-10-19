<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface SloCalculatorInterface { public function availability(array $values): float; }