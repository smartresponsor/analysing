<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\SegmentationInterface;
use SmartResponsor\Analytics\ValueObject\Analytics\Dimension;
use SmartResponsor\Analytics\ValueObject\Analytics\Segment;

final class SegmentationService implements SegmentationInterface { public function apply(array $rows, Dimension $dim, Segment $seg): array { $key=$dim->name(); $code=$seg->code(); return array_values(array_filter($rows, fn($r)=>($r[$key]??null)===$code)); }}