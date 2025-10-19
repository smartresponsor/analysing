<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface CsvImportInterface { public function read(string $csvPath, string $delimiter=','): array; }