<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface NotifierInterface { public function send(string $endpoint, array $payload): bool; }