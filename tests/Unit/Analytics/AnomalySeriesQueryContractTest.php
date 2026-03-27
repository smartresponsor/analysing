<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class AnomalySeriesQueryContractTest extends TestCase
{
    public function testAnomalySeriesQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/anomaly/series.sql');

        foreach (['{tenant_id}', '{event_name}', '{days}'] as $placeholder) {
            self::assertStringContainsString($placeholder, $sql);
        }

        foreach (['AS tenant_id', 'AS event_name', 'AS lookback_days', 'AS day', 'AS user_count'] as $column) {
            self::assertStringContainsString($column, $sql);
        }
    }
}
