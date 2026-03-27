<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ComposerManifestTest extends TestCase
{
    public function testComposerManifestKeepsCanonicalAutoloadAndCorePackages(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../../composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['App\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame(['App\\Tests\\' => 'tests/'], $composer['autoload-dev']['psr-4']);
        self::assertSame('project', $composer['type']);
        self::assertSame('dev@highhopesamerica.com', $composer['authors'][0]['email']);
        self::assertArrayHasKey('symfony/framework-bundle', $composer['require']);
        self::assertArrayHasKey('doctrine/dbal', $composer['require']);
        self::assertArrayHasKey('phpunit/phpunit', $composer['require-dev']);
    }
}
