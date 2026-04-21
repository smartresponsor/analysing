<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

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
     *   idempotency_enabled: bool,
     *   idempotency_mode: string,
     *   idempotency_required: bool,
     *   auth_required: bool,
     *   auth_public_read: bool,
     *   rate_limit_enabled: bool,
     *   rate_limit_mode: string,
     *   rate_limit_window_seconds: int,
     *   rate_limit_default_write_limit: int,
     *   tenant_context_enabled: bool,
     *   tenant: string,
     *   storage_prepare_command: string,
     *   storage_required_tables: list<string>,
     *   duration_ms: int
     * }
     */
    public function status(): array;
}
