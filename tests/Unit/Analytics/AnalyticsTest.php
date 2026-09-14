<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface;
use App\Analysing\Service\Analytics;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsTest extends TestCase
{
    public function testRunFunnelNormalizesStepsAndDelegatesToRepository(): void
    {
        $repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('fetchFunnel')
            ->with(
                'shop',
                'prod',
                ['view', 'cart', 'checkout'],
                self::callback(static fn (\DateTimeImmutable $value): bool => '2026-01-01 00:00:00' === $value->format('Y-m-d H:i:s')),
                self::callback(static fn (\DateTimeImmutable $value): bool => '2026-01-31 23:59:59' === $value->format('Y-m-d H:i:s')),
            )
            ->willReturn([['day' => '2026-01-01', 'user_count' => 5]]);

        $service = new Analytics($repository, new NullLogger());
        $rows = $service->runFunnel([
            'vendor_id' => 'vendor-1',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-31 23:59:59',
            'steps' => ['view', 'cart', 'checkout', 'checkout'],
        ]);

        self::assertSame([['day' => '2026-01-01', 'user_count' => 5]], $rows);
    }

    public function testRunRetentionRejectsInvalidDateOrder(): void
    {
        $repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $repository->expects(self::never())->method('fetchRetention');
        $service = new Analytics($repository, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->runRetention([
            'vendor_id' => 'vendor-1',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-02-01 00:00:00',
            'to' => '2026-01-01 00:00:00',
            'cohort' => '2026-01-01 00:00:00',
            'days' => 30,
        ]);
    }
}
