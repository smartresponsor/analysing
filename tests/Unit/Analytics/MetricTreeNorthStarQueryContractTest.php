<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class MetricTreeNorthStarQueryContractTest extends TestCase
{
    public function testNorthStarMetricTreeQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/metric_tree/north_star_root.sql');

        self::assertStringContainsString('{tenant_id}', $sql);
        self::assertStringContainsString('AS tenant_id', $sql);
        self::assertStringContainsString('AS value', $sql);
    }
}
