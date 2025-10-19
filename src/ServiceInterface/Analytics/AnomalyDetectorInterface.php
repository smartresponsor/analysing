<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface AnomalyDetectorInterface { public function zscore(array $values): array; }