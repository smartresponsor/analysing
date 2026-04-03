<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface HealthServiceInterface
{
    /**
     * @return array{
     *   ok: bool,
     *   component: string,
     *   time: string,
     *   php_version: string,
     *   sapi: string,
     *   missing_required_extensions: list<string>,
     *   missing_optional_extensions: list<string>,
     *   kpi_catalog_count: int,
     *   kpi_catalog_checksum: ?string,
     *   storage_driver: string,
     *   storage_mode: string,
     *   storage_available: bool,
     *   duration_ms: int
     * }
     */
    public function status(): array;
}
