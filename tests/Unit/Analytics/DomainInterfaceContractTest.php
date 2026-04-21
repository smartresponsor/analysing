<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class DomainInterfaceContractTest extends TestCase
{
    public function testDomainsImplementTheirInterfaces(): void
    {
        $map = [
            'App\Analysing\\Domain\\Analytics\\Analytics' => 'App\Analysing\\DomainInterface\\Analytics\\AnalyticsInterface',
            'App\Analysing\\Domain\\Analytics\\ClickhouseClient' => 'App\Analysing\\DomainInterface\\Analytics\\ClickhouseClientInterface',
            'App\Analysing\\Domain\\Analytics\\Experiment' => 'App\Analysing\\DomainInterface\\Analytics\\ExperimentInterface',
            'App\Analysing\\Domain\\Analytics\\Flag' => 'App\Analysing\\DomainInterface\\Analytics\\FlagInterface',
            'App\Analysing\\Domain\\Analytics\\Insight' => 'App\Analysing\\DomainInterface\\Analytics\\InsightInterface',
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
