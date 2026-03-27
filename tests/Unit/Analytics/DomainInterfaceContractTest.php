<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class DomainInterfaceContractTest extends TestCase
{
    public function testDomainsImplementTheirInterfaces(): void
    {
        $map = [
            'App\\Domain\\Analytics\\Analytics' => 'App\\DomainInterface\\Analytics\\AnalyticsInterface',
            'App\\Domain\\Analytics\\ClickhouseClient' => 'App\\DomainInterface\\Analytics\\ClickhouseClientInterface',
            'App\\Domain\\Analytics\\Experiment' => 'App\\DomainInterface\\Analytics\\ExperimentInterface',
            'App\\Domain\\Analytics\\Flag' => 'App\\DomainInterface\\Analytics\\FlagInterface',
            'App\\Domain\\Analytics\\Insight' => 'App\\DomainInterface\\Analytics\\InsightInterface',
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
