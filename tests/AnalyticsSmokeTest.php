<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Entity\Analytics\MetricSnapshot;

final class AnalyticsSmokeTest extends TestCase
{
    public function testEntityConstruct(): void
    {
        $m = new MetricSnapshot(1, 'USD', 1000, 900, '42.5');
        $this->assertTrue(true);
    }
}
