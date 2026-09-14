<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServiceInterfaceContractTest extends TestCase
{
    public function testServicesImplementTheirInterfaces(): void
    {
        $map = [
            'App\Analysing\\Service\\Analytics' => 'App\Analysing\\ServiceInterface\\AnalyticsInterface',
            'App\Analysing\\Service\\AnalyticsClickhouseClient' => 'App\Analysing\\ServiceInterface\\AnalyticsClickhouseClientInterface',
            'App\Analysing\\Service\\AnalyticsExperiment' => 'App\Analysing\\ServiceInterface\\AnalyticsExperimentInterface',
            'App\Analysing\\Service\\AnalyticsFlag' => 'App\Analysing\\ServiceInterface\\AnalyticsFlagInterface',
            'App\Analysing\\Service\\AnalyticsInsight' => 'App\Analysing\\ServiceInterface\\AnalyticsInsightInterface',
        ];

        foreach ($map as $class => $interface) {
            $classReflection = new \ReflectionClass($class);
            $interfaceReflection = new \ReflectionClass($interface);

            self::assertTrue($classReflection->implementsInterface($interface));

            foreach ($interfaceReflection->getMethods() as $method) {
                self::assertTrue($classReflection->hasMethod($method->getName()), sprintf('%s::%s is missing.', $class, $method->getName()));
            }
        }
    }
}
