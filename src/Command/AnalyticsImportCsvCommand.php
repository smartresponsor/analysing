<?php

namespace SmartResponsor\Analytics\Command;
use SmartResponsor\Analytics\Service\Analytics\CsvImporter;

final class AnalyticsImportCsvCommand { public function __construct(private CsvImporter $imp) {} public function run(string $path): int { return count($this->imp->read($path)); }}