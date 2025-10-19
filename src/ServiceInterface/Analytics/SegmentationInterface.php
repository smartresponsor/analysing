<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

use SmartResponsor\Analytics\ValueObject\Analytics\Dimension; use SmartResponsor\Analytics\ValueObject\Analytics\Segment; interface SegmentationInterface { public function apply(array $rows, Dimension $dim, Segment $seg): array; }