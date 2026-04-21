<?php

declare(strict_types=1);

namespace App\Analysing\Tests;

use App\Analysing\Entity\Analytics\MetricSnapshot;
use PHPUnit\Framework\TestCase;

final class AnalyticsSmokeTest extends TestCase
{
    public function testEntityConstruct(): void
    {
        $m = new MetricSnapshot(
            'gmv',
            42.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-31 23:59:59'),
            ['currency' => 'USD']
        );
        $this->assertTrue($m instanceof MetricSnapshot);
    }
}
