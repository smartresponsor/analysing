<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;

final class ConnectionFactory
{
    public function create(): Connection
    {
        $url = getenv('ANALYTICS_DATABASE_URL');
        if (is_string($url) && '' !== trim($url)) {
            $normalizedUrl = trim($url);
            $this->assertExternalDriverAvailable($normalizedUrl);

            return DriverManager::getConnection([
                'url' => $normalizedUrl,
            ], $this->createConfiguration());
        }

        $this->assertSqliteDriverAvailable();

        $path = dirname(__DIR__, 3).'/var/analytics.sqlite';
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ], $this->createConfiguration());
    }


    private function assertExternalDriverAvailable(string $url): void
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (in_array($scheme, ['sqlite', 'pdo-sqlite'], true)) {
            $this->assertSqliteDriverAvailable();
        }
    }

    private function assertSqliteDriverAvailable(): void
    {
        if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException('Analytics storage requires the pdo_sqlite extension or a working ANALYTICS_DATABASE_URL driver. Current runtime cannot open SQLite storage.');
        }
    }

    private function createConfiguration(): Configuration
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }
}
