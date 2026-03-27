<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\ExperimentService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ExperimentServiceTest extends TestCase
{
    public function testChooseIsDeterministicForSameSubject(): void
    {
        $service = new ExperimentService(new NullLogger(), [
            'checkout-banner' => ['A' => 1, 'B' => 2],
        ]);

        $first = $service->choose('checkout-banner', 'user-42');
        $second = $service->choose('checkout-banner', 'user-42');

        self::assertSame($first, $second);
        self::assertContains($first, ['A', 'B']);
    }

    public function testRecordRejectsNonFiniteValue(): void
    {
        $service = new ExperimentService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->record('exp-1', 'A', 'conversion', INF);
    }

    public function testRecordBufferIsBounded(): void
    {
        $service = new ExperimentService(new NullLogger());

        for ($i = 0; $i < 1005; ++$i) {
            $service->record('exp-1', 'A', 'metric', (float) $i);
        }

        $property = new \ReflectionProperty($service, 'recorded');
        $recorded = $property->getValue($service);

        self::assertCount(1000, $recorded);
        self::assertSame(5.0, $recorded[0]['value']);
        self::assertSame(1004.0, $recorded[999]['value']);
    }
}
