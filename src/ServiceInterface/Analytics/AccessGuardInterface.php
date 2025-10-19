<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface AccessGuardInterface { public function allow(string $subject): bool; }