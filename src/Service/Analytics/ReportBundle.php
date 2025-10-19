<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\ReportBundleInterface;

final class ReportBundle implements ReportBundleInterface { public function pack(array $datasets): array { $manifest=[]; foreach($datasets as $name=>$rows){ $manifest[$name]=['rows'=>count($rows)]; } return $manifest; }}