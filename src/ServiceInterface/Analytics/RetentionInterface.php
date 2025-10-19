<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface RetentionInterface { public function prune(array $rows, int $maxDays): array; }