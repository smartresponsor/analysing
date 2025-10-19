<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface GzipWriterInterface { public function write(string $path, array $rows): string; }