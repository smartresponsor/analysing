<?php

namespace SmartResponsor\Analytics\Controller\Analytics;

final class ApiController { public function metrics(): array { return ['ok'=>true,'data'=>[['k'=>'orders_per_day','v'=>42],['k'=>'revenue_total','v'=>12345.67]]]; }}