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
            return DriverManager::getConnection([
                'url' => trim($url),
            ], $this->createConfiguration());
        }

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

    private function createConfiguration(): Configuration
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }
}
