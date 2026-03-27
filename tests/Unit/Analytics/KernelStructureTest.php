<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class KernelStructureTest extends TestCase
{
    public function testKernelLoadsBundlesServicesAndRoutes(): void
    {
        $php = (string) file_get_contents(__DIR__.'/../../../src/Kernel.php');

        self::assertStringContainsString('MicroKernelTrait', $php);
        self::assertStringContainsString("\$contents = require \$this->getProjectDir().'/config/bundles.php';", $php);
        self::assertStringContainsString("\$loader->load(\$configDir.'/packages/*.yaml', 'glob');", $php);
        self::assertStringContainsString("\$loader->load(\$configDir.'/services.yaml');", $php);
        self::assertStringContainsString("\$routes->import(\$configDir.'/routes.yaml');", $php);
    }
}
