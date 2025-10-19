<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\AnomalyDetectorInterface;

final class AnomalyDetector implements AnomalyDetectorInterface { public function zscore(array $values): array { $n=count($values); if($n===0) return []; $mean=array_sum($values)/$n; $variance=0.0; foreach($values as $v){ $variance += ($v-$mean)**2; } $std=sqrt($variance / max(1,$n)); return array_map(fn($v)=> $std>0 ? ($v-$mean)/$std : 0.0, $values); }}