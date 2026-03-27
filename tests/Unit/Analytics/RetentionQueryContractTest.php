<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class RetentionQueryContractTest extends TestCase
{
    public function testRetentionQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/retention.sql');

        foreach (['{tenant_id}', '{app}', '{env}', '{from}', '{to}', '{cohort}', '{days}'] as $placeholder) {
            self::assertStringContainsString($placeholder, $sql);
        }

        foreach (['AS tenant_id', 'AS app', 'AS env', 'AS date_from', 'AS date_to', 'AS cohort_date', 'AS retention_days', 'AS day_offset', 'AS active_user'] as $column) {
            self::assertStringContainsString($column, $sql);
        }
    }
}
