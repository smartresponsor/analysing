<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ControllerInterfaceContractTest extends TestCase
{
    public function testControllersImplementTheirInterfaces(): void
    {
        $map = [
            'App\\Controller\\Analytics\\AggregateController' => 'App\\ControllerInterface\\Analytics\\AggregateControllerInterface',
            'App\\Controller\\Analytics\\AnalyticsController' => 'App\\ControllerInterface\\Analytics\\AnalyticsControllerInterface',
            'App\\Controller\\Analytics\\ApiController' => 'App\\ControllerInterface\\Analytics\\ApiControllerInterface',
            'App\\Controller\\Analytics\\DashboardController' => 'App\\ControllerInterface\\Analytics\\DashboardControllerInterface',
            'App\\Controller\\Analytics\\DashboardPageController' => 'App\\ControllerInterface\\Analytics\\DashboardPageControllerInterface',
            'App\\Controller\\Analytics\\ExperimentController' => 'App\\ControllerInterface\\Analytics\\ExperimentControllerInterface',
            'App\\Controller\\Analytics\\FlagController' => 'App\\ControllerInterface\\Analytics\\FlagControllerInterface',
            'App\\Controller\\Analytics\\HealthController' => 'App\\ControllerInterface\\Analytics\\HealthControllerInterface',
            'App\\Controller\\Analytics\\IngestController' => 'App\\ControllerInterface\\Analytics\\IngestControllerInterface',
            'App\\Controller\\Analytics\\InsightController' => 'App\\ControllerInterface\\Analytics\\InsightControllerInterface',
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
