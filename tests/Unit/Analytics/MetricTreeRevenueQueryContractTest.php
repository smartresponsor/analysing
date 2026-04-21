<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class MetricTreeRevenueQueryContractTest extends TestCase
{
    public function testRevenueMetricTreeQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/metric_tree/revenue.sql');

        self::assertStringContainsString('{tenant_id}', $sql);
        self::assertStringContainsString('AS tenant_id', $sql);
        self::assertStringContainsString('AS value', $sql);
    }
}
