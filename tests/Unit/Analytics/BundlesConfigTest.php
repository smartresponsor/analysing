<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\AnalysingBundle;
use App\Analysing\DependencyInjection\AnalysingExtension;
use PHPUnit\Framework\TestCase;

final class BundlesConfigTest extends TestCase
{
    public function testBundleSurfacePointsToAnalysingNamespace(): void
    {
        $bundle = new AnalysingBundle();
        $metadata = (string) file_get_contents(__DIR__.'/../../../config/component/component.yaml');

        self::assertInstanceOf(AnalysingExtension::class, $bundle->getContainerExtension());
        self::assertStringContainsString('namespace: App\\Analysing', $metadata);
        self::assertStringContainsString('class: App\\Analysing\\AnalysingBundle', $metadata);
        self::assertStringContainsString('extension: App\\Analysing\\DependencyInjection\\AnalysingExtension', $metadata);
    }
}
