<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class RepositoryInterfaceContractTest extends TestCase
{
    public function testInfraRepositoryImplementsItsInterface(): void
    {
        $repositoryReflection = new \ReflectionClass('App\\Repository\\Analytics\\InfraRepository');
        $interfaceReflection = new \ReflectionClass('App\\RepositoryInterface\\Analytics\\InfraRepositoryInterface');

        self::assertTrue($repositoryReflection->implementsInterface('App\\RepositoryInterface\\Analytics\\InfraRepositoryInterface'));

        foreach ($interfaceReflection->getMethods() as $method) {
            self::assertTrue($repositoryReflection->hasMethod($method->getName()), sprintf('InfraRepository::%s is missing.', $method->getName()));
        }
    }
}
