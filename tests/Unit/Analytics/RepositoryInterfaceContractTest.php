<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class RepositoryInterfaceContractTest extends TestCase
{
    public function testInfraRepositoryImplementsItsInterface(): void
    {
        $repositoryReflection = new \ReflectionClass('App\Analysing\\Repository\\Analytics\\InfraRepository');
        $interfaceReflection = new \ReflectionClass('App\Analysing\\RepositoryInterface\\Analytics\\InfraRepositoryInterface');

        self::assertTrue($repositoryReflection->implementsInterface('App\Analysing\\RepositoryInterface\\Analytics\\InfraRepositoryInterface'));

        foreach ($interfaceReflection->getMethods() as $method) {
            self::assertTrue($repositoryReflection->hasMethod($method->getName()), sprintf('InfraRepository::%s is missing.', $method->getName()));
        }
    }
}
