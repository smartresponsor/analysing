<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ControllerInterfaceContractTest extends TestCase
{
    public function testControllersImplementTheirInterfaces(): void
    {
        $map = [
            'App\Analysing\\Controller\\Analytics\\AggregateController' => 'App\Analysing\\ControllerInterface\\Analytics\\AggregateControllerInterface',
            'App\Analysing\\Controller\\Analytics\\AnalyticsController' => 'App\Analysing\\ControllerInterface\\Analytics\\AnalyticsControllerInterface',
            'App\Analysing\\Controller\\Analytics\\ApiController' => 'App\Analysing\\ControllerInterface\\Analytics\\ApiControllerInterface',
            'App\Analysing\\Controller\\Analytics\\DashboardController' => 'App\Analysing\\ControllerInterface\\Analytics\\DashboardControllerInterface',
            'App\Analysing\\Controller\\Analytics\\DashboardPageController' => 'App\Analysing\\ControllerInterface\\Analytics\\DashboardPageControllerInterface',
            'App\Analysing\\Controller\\Analytics\\ExperimentController' => 'App\Analysing\\ControllerInterface\\Analytics\\ExperimentControllerInterface',
            'App\Analysing\\Controller\\Analytics\\FlagController' => 'App\Analysing\\ControllerInterface\\Analytics\\FlagControllerInterface',
            'App\Analysing\\Controller\\Analytics\\HealthController' => 'App\Analysing\\ControllerInterface\\Analytics\\HealthControllerInterface',
            'App\Analysing\\Controller\\Analytics\\IngestController' => 'App\Analysing\\ControllerInterface\\Analytics\\IngestControllerInterface',
            'App\Analysing\\Controller\\Analytics\\InsightController' => 'App\Analysing\\ControllerInterface\\Analytics\\InsightControllerInterface',
        ];

        foreach ($map as $controller => $interface) {
            $controllerReflection = new \ReflectionClass($controller);
            $interfaceReflection = new \ReflectionClass($interface);

            self::assertTrue($controllerReflection->implementsInterface($interface));

            foreach ($interfaceReflection->getMethods() as $method) {
                self::assertTrue($controllerReflection->hasMethod($method->getName()), sprintf('%s::%s is missing.', $controller, $method->getName()));
            }
        }
    }
}
