<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Repository\Analytics\SampleInfraRepository;
use App\Analysing\Service\Analytics\SampleAnalyticsDataset;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SampleInfraRepositoryTest extends TestCase
{
    public function testFetchFunnelReturnsSampleRows(): void
    {
        $repository = new SampleInfraRepository(new SampleAnalyticsDataset(), new NullLogger());

        $rows = $repository->fetchFunnel(
            'shop',
            'prod',
            ['view', 'checkout'],
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-03 23:59:59'),
        );

        self::assertCount(3, $rows);
        self::assertSame('2026-01-01', $rows[0]['day']);
        self::assertSame(250, $rows[0]['user_count']);
    }

    public function testFetchPathHonorsTopLimit(): void
    {
        $repository = new SampleInfraRepository(new SampleAnalyticsDataset(), new NullLogger());

        $rows = $repository->fetchPath('shop', 'prod', new \DateTimeImmutable('2026-01-10 00:00:00'), 2);

        self::assertCount(2, $rows);
        self::assertSame('view', $rows[0]['from_event']);
        self::assertSame('add_to_cart', $rows[0]['to_event']);
    }
}
