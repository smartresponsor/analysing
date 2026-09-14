<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TransformerTest extends TestCase
{
    public function testMapAppliesCallableAcrossRows(): void
    {
        $service = new AnalyticsTransformer(new NullLogger());
        $mapped = $service->map([
            ['value' => 1],
            ['value' => 2],
        ], static fn (array $row): int => $row['value'] * 2);

        self::assertSame([2, 4], $mapped);
    }

    public function testMapWrapsCallableFailure(): void
    {
        $service = new AnalyticsTransformer(new NullLogger());

        $this->expectException(\RuntimeException::class);
        $service->map([
            ['value' => 1],
        ], static function (): int {
            throw new \LogicException('boom');
        });
    }
}
