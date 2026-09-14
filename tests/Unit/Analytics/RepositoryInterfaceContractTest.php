<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class RepositoryInterfaceContractTest extends TestCase
{
    public function testAnalyticsRepositoryImplementsItsInterface(): void
    {
        $repositoryReflection = new \ReflectionClass('App\Analysing\Repository\AnalyticsRepository');
        $interfaceReflection = new \ReflectionClass('App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface');

        self::assertTrue($repositoryReflection->implementsInterface('App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface'));

        foreach ($interfaceReflection->getMethods() as $method) {
            self::assertTrue($repositoryReflection->hasMethod($method->getName()), sprintf('AnalyticsRepository::%s is missing.', $method->getName()));
        }
    }
}
