<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\ReportBundle;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportBundleTest extends TestCase
{
    public function testPackBuildsDeterministicManifest(): void
    {
        $bundle = new ReportBundle(new NullLogger());

        $manifest = $bundle->pack([
            'orders' => [
                ['b' => 2, 'a' => 1],
                ['c' => 3],
            ],
        ]);

        self::assertSame([
            'orders' => [
                'rows' => 2,
                'columns' => ['a', 'b', 'c'],
            ],
        ], $manifest);
    }

    public function testPackRejectsDuplicateDatasetNamesAfterNormalization(): void
    {
        $bundle = new ReportBundle(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $bundle->pack([
            ' orders ' => [],
            'orders' => [],
        ]);
    }
}
