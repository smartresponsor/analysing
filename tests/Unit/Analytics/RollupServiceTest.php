<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsRollupService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RollupServiceTest extends TestCase
{
    public function testSumSumsNumericFieldValues(): void
    {
        $service = new AnalyticsRollupService(new NullLogger());

        self::assertSame(3.5, $service->sum([
            ['value' => 1],
            ['value' => '2.5'],
            ['value' => 'not-numeric'],
        ], 'value'));
    }

    public function testSumRejectsEmptyField(): void
    {
        $service = new AnalyticsRollupService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->sum([], '   ');
    }
}
