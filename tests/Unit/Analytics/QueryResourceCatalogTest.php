<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class QueryResourceCatalogTest extends TestCase
{
    public function testLiveAnalyticsQueryResourcesExistAndAreNonEmpty(): void
    {
        $paths = [
            'src/queries/funnel.sql',
            'src/queries/retention.sql',
            'src/queries/cohort.sql',
            'src/queries/anomaly/series.sql',
            'src/queries/metric_tree/north_star_root.sql',
            'src/queries/metric_tree/activation.sql',
            'src/queries/metric_tree/revenue.sql',
            'src/metric_tree/catalog.json',
        ];

        foreach ($paths as $relativePath) {
            $path = __DIR__.'/../../../'.$relativePath;
            self::assertFileExists($path);
            self::assertNotSame('', trim((string) file_get_contents($path)), $relativePath.' should not be empty.');
        }
    }
}
