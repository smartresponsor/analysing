<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class KernelStructureTest extends TestCase
{
    public function testTestKernelLoadsComponentServicesAndRoutes(): void
    {
        $php = (string) file_get_contents(__DIR__.'/../../Support/TestKernel.php');

        self::assertStringContainsString('yield new FrameworkBundle();', $php);
        self::assertStringContainsString('yield new AnalysingBundle();', $php);
        self::assertStringContainsString("\$loader->load(\$configDir.'/component/services.yaml');", $php);
        self::assertStringContainsString("\$routes->import(\$configDir.'/component/routes.yaml');", $php);
    }
}
