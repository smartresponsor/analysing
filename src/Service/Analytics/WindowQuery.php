<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\WindowQueryInterface;

final class WindowQuery implements WindowQueryInterface { public function window(array $rows, int $size): array { $out=[]; for($i=0;$i<count($rows);$i+=$size){ $out[]=array_slice($rows,$i,$size);} return $out; }}