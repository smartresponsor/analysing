<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Service\Http\TenantContext;
use App\ServiceInterface\Analytics\HealthServiceInterface;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use Psr\Log\LoggerInterface;

final class HealthService implements HealthServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly KpiRegistryInterface $registry,
        private readonly string $storageDriver = 'unknown',
        private readonly string $storageMode = 'unknown',
        private readonly bool $storageAvailable = false,
        private readonly bool $idempotencyEnabled = false,
        private readonly string $idempotencyMode = 'disabled',
        private readonly bool $idempotencyRequired = false,
        private readonly bool $authRequired = false,
        private readonly bool $authPublicRead = true,
        private readonly bool $rateLimitEnabled = false,
        private readonly string $rateLimitMode = 'disabled',
        private readonly int $rateLimitWindowSeconds = 60,
        private readonly int $rateLimitDefaultWriteLimit = 60,
        private readonly bool $tenantContextEnabled = true,
        private readonly ?TenantContext $tenantContext = null,
        private readonly string $storagePrepareCommand = 'php bin/console analytics:storage:prepare --seed',
        /** @var list<string> */
        private readonly array $storageRequiredTables = [],
    ) {
    }

    public function status(): array
    {
        $startedAt = microtime(true);
        $requiredExtensions = ['json', 'pdo'];
        $optionalExtensions = ['mbstring', 'curl', 'zlib'];
        $missingRequiredExtensions = [];
        $missingOptionalExtensions = [];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingRequiredExtensions[] = $extension;
            }
        }

        foreach ($optionalExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingOptionalExtensions[] = $extension;
            }
        }

        if ([] !== $missingRequiredExtensions) {
            $this->logger->warning('Analytics health service detected missing required PHP extensions.', [
                'missing_extensions' => $missingRequiredExtensions,
            ]);
        }

        if ([] !== $missingOptionalExtensions) {
            $this->logger->info('Analytics health service detected missing optional PHP extensions.', [
                'missing_extensions' => $missingOptionalExtensions,
            ]);
        }

        $catalogCount = 0;
        $catalogChecksum = null;
        try {
            $catalog = $this->registry->list();
            $catalogCount = count($catalog);
            if ([] !== $catalog) {
                $catalogChecksum = hash('sha256', json_encode($catalog, JSON_THROW_ON_ERROR));
            }
        } catch (\RuntimeException|\JsonException|\Throwable $exception) {
            $this->logger->error('Analytics health service could not inspect the KPI catalog.', [
                'exception' => $exception,
            ]);
        }

        if (0 === $catalogCount) {
            $this->logger->warning('Analytics health service detected an empty KPI catalog.');
        }

        return [
            'ok' => [] === $missingRequiredExtensions && $catalogCount > 0,
            'component' => 'analytics',
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'php_version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'missing_required_extensions' => $missingRequiredExtensions,
            'missing_optional_extensions' => $missingOptionalExtensions,
            'kpi_catalog_count' => $catalogCount,
            'kpi_catalog_checksum' => $catalogChecksum,
            'storage_driver' => $this->storageDriver,
            'storage_mode' => $this->storageMode,
            'storage_available' => $this->storageAvailable,
            'idempotency_enabled' => $this->idempotencyEnabled,
            'idempotency_mode' => $this->idempotencyMode,
            'idempotency_required' => $this->idempotencyRequired,
            'auth_required' => $this->authRequired,
            'auth_public_read' => $this->authPublicRead,
            'rate_limit_enabled' => $this->rateLimitEnabled,
            'rate_limit_mode' => $this->rateLimitMode,
            'rate_limit_window_seconds' => $this->rateLimitWindowSeconds,
            'rate_limit_default_write_limit' => $this->rateLimitDefaultWriteLimit,
            'tenant_context_enabled' => $this->tenantContextEnabled,
            'tenant' => $this->tenantContext?->current() ?? 'public',
            'storage_prepare_command' => $this->storagePrepareCommand,
            'storage_required_tables' => $this->storageRequiredTables,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
