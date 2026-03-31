<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\HealthServiceInterface;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use Psr\Log\LoggerInterface;

final class HealthService implements HealthServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly KpiRegistryInterface $registry,
    ) {
    }

    public function status(): array
    {
        $startedAt = microtime(true);
        $requiredExtensions = ['json', 'pdo', 'mbstring'];
        $optionalExtensions = ['curl', 'zlib'];
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

        $catalogReady = $catalogCount > 0;
        if (!$catalogReady) {
            $this->logger->warning('Analytics health service detected an empty KPI catalog.');
        }

        return [
            'ok' => [] === $missingRequiredExtensions,
            'component' => 'analytics',
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'php_version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'missing_required_extensions' => $missingRequiredExtensions,
            'missing_optional_extensions' => $missingOptionalExtensions,
            'kpi_catalog_count' => $catalogCount,
            'kpi_catalog_checksum' => $catalogChecksum,
            'catalog_ready' => $catalogReady,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
