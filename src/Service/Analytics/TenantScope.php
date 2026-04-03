<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\TenantScopeInterface;
use App\ValueObject\Analytics\TenantId;
use Psr\Log\LoggerInterface;

final class TenantScope implements TenantScopeInterface
{
    private const MAX_TENANT_LENGTH = 128;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function filter(array $rows, TenantId $tenant): array
    {
        $tenantValue = trim($tenant->value());
        if ('' === $tenantValue) {
            $this->logger->warning('Analytics tenant scope rejected an empty tenant identifier.');
            throw new \InvalidArgumentException('Tenant scope requires a non-empty tenant identifier.');
        }

        if (strlen($tenantValue) > self::MAX_TENANT_LENGTH) {
            $this->logger->warning('Analytics tenant scope rejected an overlong tenant identifier.', [
                'tenant_length' => strlen($tenantValue),
                'max_length' => self::MAX_TENANT_LENGTH,
            ]);
            throw new \InvalidArgumentException('Tenant identifier exceeds the maximum allowed length.');
        }

        $filtered = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                ++$skipped;
                $this->logger->warning('Analytics tenant scope skipped non-array row.', [
                    'index' => $index,
                    'type' => get_debug_type($row),
                ]);
                continue;
            }

            $rowTenantValue = $row['tenant'] ?? '';
            $rowTenant = is_scalar($rowTenantValue) ? trim((string) $rowTenantValue) : '';
            if ('' === $rowTenant) {
                ++$skipped;
                $this->logger->warning('Analytics tenant scope skipped row without tenant.', [
                    'index' => $index,
                ]);
                continue;
            }

            if (strlen($rowTenant) > self::MAX_TENANT_LENGTH) {
                ++$skipped;
                $this->logger->warning('Analytics tenant scope skipped row with an overlong tenant value.', [
                    'index' => $index,
                    'tenant_length' => strlen($rowTenant),
                    'max_length' => self::MAX_TENANT_LENGTH,
                ]);
                continue;
            }

            if ($rowTenant === $tenantValue) {
                $filtered[] = $row;
            }
        }

        $this->logger->info('Analytics tenant scope completed.', [
            'tenant' => $tenantValue,
            'rows' => count($rows),
            'matched_rows' => count($filtered),
            'skipped_rows' => $skipped,
        ]);

        return array_values($filtered);
    }
}
