<?php

namespace SmartResponsor\Analytics\Service\Analytics;
use SmartResponsor\Analytics\ServiceInterface\Analytics\AccessGuardInterface;

final class AccessGuard implements AccessGuardInterface { public function __construct(private array $allow=[]) {} public function allow(string $subject): bool { if($this->allow===[]) return true; return in_array($subject,$this->allow,true); }}