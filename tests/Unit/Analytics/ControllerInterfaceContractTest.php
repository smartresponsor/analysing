<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ControllerInterfaceContractTest extends TestCase
{
    public function testControllersImplementTheirInterfaces(): void
    {
        $map = [
            'App\Analysing\Controller\AnalyticsAggregateController' => 'App\Analysing\Controller\AnalyticsAggregateControllerInterface',
            'App\Analysing\Controller\AnalyticsController' => 'App\Analysing\Controller\AnalyticsControllerInterface',
            'App\Analysing\Controller\AnalyticsApiController' => 'App\Analysing\Controller\AnalyticsApiControllerInterface',
            'App\Analysing\Controller\AnalyticsDashboardController' => 'App\Analysing\Controller\AnalyticsDashboardControllerInterface',
            'App\Analysing\Controller\AnalyticsDashboardPageController' => 'App\Analysing\Controller\AnalyticsDashboardPageControllerInterface',
            'App\Analysing\Controller\AnalyticsExperimentController' => 'App\Analysing\Controller\AnalyticsExperimentControllerInterface',
            'App\Analysing\Controller\AnalyticsFlagController' => 'App\Analysing\Controller\AnalyticsFlagControllerInterface',
            'App\Analysing\Controller\AnalyticsHealthController' => 'App\Analysing\Controller\AnalyticsHealthControllerInterface',
            'App\Analysing\Controller\AnalyticsIngestController' => 'App\Analysing\Controller\AnalyticsIngestControllerInterface',
            'App\Analysing\Controller\AnalyticsInsightController' => 'App\Analysing\Controller\AnalyticsInsightControllerInterface',
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
