<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\RetentionInterface;

final class RetentionService implements RetentionInterface { public function prune(array $rows, int $maxDays): array { $border=time()-$maxDays*86400; return array_values(array_filter($rows, fn($r)=>(int)($r['ts']??0)>=$border)); }}