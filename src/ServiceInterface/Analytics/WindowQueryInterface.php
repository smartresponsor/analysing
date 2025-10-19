<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface WindowQueryInterface { public function window(array $rows, int $size): array; }