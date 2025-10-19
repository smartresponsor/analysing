<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\KpiRegistryInterface;
use SmartResponsor\Analytics\ValueObject\Analytics\KpiId;

final class KpiRegistry implements KpiRegistryInterface { private array $map; public function __construct(array $map=[]){$this->map=$map?:['orders_per_day'=>'Orders created per day','revenue_total'=>'Total revenue recognized','alerts_open'=>'Open alerts count']; } public function list(): array { return $this->map; } public function has(KpiId $id): bool { return isset($this->map[(string)$id]); }}