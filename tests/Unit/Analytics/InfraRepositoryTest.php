<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Repository\Analytics\InfraRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InfraRepositoryTest extends TestCase
{
    public function testFetchFunnelNormalizesRows(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['day' => '2026-01-01', 'user_count' => '12'],
            ]);

        $repository = new InfraRepository($connection, new NullLogger());
        $rows = $repository->fetchFunnel('shop', 'prod', ['view', 'cart'], new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-01-07'));

        self::assertSame([['day' => '2026-01-01', 'user_count' => 12]], $rows);
    }

    public function testFetchPathRejectsBrokenRowShape(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['from_event' => 'view', 'transition_count' => 7],
            ]);

        $repository = new InfraRepository($connection, new NullLogger());

        $this->expectException(\RuntimeException::class);
        $repository->fetchPath('shop', 'prod', new \DateTimeImmutable('2026-01-01'), 10);
    }

    public function testUpsertRejectsNegativeExposure(): void
    {
        $repository = new InfraRepository($this->createMock(Connection::class), new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $repository->upsertExperimentMetricDaily('exp', 'A', new \DateTimeImmutable('2026-01-01'), -1, 0, 0.0);
    }
}
