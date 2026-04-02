<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Analytics\ExportJob;
use App\Tests\Fixtures\Factories\ExportJobFactory;

/**
 * Provides a baseline analytics dataset for tests.
 *
 * This class intentionally does not depend on Doctrine fixtures bundle to remain lightweight and
 * reusable across multiple testing layers.
 */
final class AnalyticsDemoFixtures
{
    /**
     * @return list<ExportJob>
     */
    public static function exportJobs(): array
    {
        return [
            ExportJobFactory::completed(['source' => 'demo']),
            ExportJobFactory::failed(['source' => 'demo']),
            ExportJobFactory::pending(['source' => 'demo']),
        ];
    }
}
