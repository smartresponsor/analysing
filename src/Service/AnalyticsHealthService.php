<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\Service\Http\AnalyticsVendorContext;
use App\Analysing\ServiceInterface\AnalyticsHealthServiceInterface;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsHealthService implements AnalyticsHealthServiceInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private AnalyticsKpiRegistryInterface $registry,
        private string $storageDriver = 'unknown',
        private string $storageMode = 'unknown',
        private bool $storageAvailable = false,
        private bool $idempotencyEnabled = false,
        private string $idempotencyMode = 'disabled',
        private bool $idempotencyRequired = false,
        private bool $authRequired = false,
        private bool $authPublicRead = true,
        private bool $rateLimitEnabled = false,
        private string $rateLimitMode = 'disabled',
        private int $rateLimitWindowSeconds = 60,
        private int $rateLimitDefaultWriteLimit = 60,
        private bool $vendorContextEnabled = true,
        private ?AnalyticsVendorContext $vendorContext = null,
        private string $storagePrepareCommand = 'php bin/console analytics:storage:prepare --seed',
        /** @var list<string> */
        private array $storageRequiredTables = ['aggregate_funnel_daily', 'retention_cohort_daily', 'path_transition_daily', 'experiment_metric_daily', 'analytics_alert_rule'],
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
        } catch (\Throwable $exception) {
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
            'vendor_context_enabled' => $this->vendorContextEnabled,
            'vendor' => $this->vendorContext?->current() ?? 'public',
            'storage_prepare_command' => $this->storagePrepareCommand,
            'storage_required_tables' => $this->storageRequiredTables,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
