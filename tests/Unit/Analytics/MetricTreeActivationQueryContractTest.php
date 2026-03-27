<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class MetricTreeActivationQueryContractTest extends TestCase
{
    public function testActivationMetricTreeQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/metric_tree/activation.sql');

        self::assertStringContainsString('{tenant_id}', $sql);
        self::assertStringContainsString('AS tenant_id', $sql);
        self::assertStringContainsString('AS value', $sql);
    }
}
