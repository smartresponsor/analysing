<?php

namespace SmartResponsor\Analytics\ServiceInterface\Analytics;

interface ReportBundleInterface { public function pack(array $datasets): array; }