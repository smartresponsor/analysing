<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class FunnelQueryContractTest extends TestCase
{
    public function testFunnelQueryContainsExpectedBindingsAndOutputColumns(): void
    {
        $sql = (string) file_get_contents(__DIR__.'/../../../src/queries/funnel.sql');

        foreach (['{step_count}', '{tenant_id}', '{app}', '{env}', '{from}', '{to}', '{step_1}', '{step_2}', '{step_3}', '{step_4}'] as $placeholder) {
            self::assertStringContainsString($placeholder, $sql);
        }

        foreach (['AS step_count', 'AS tenant_id', 'AS app', 'AS env', 'AS date_from', 'AS date_to', 'AS first_step', 'AS second_step', 'AS user_count'] as $column) {
            self::assertStringContainsString($column, $sql);
        }
    }
}
