<?php

declare(strict_types=1);

namespace App\Analysing\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class EntityManagerFactory
{
    public function create(Connection $connection): EntityManagerInterface
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__, 3).'/src/Entity'],
            isDevMode: $this->isDebug(),
        );

        return new EntityManager($connection, $config);
    }

    private function isDebug(): bool
    {
        $value = getenv('APP_DEBUG');

        return false !== $value && '0' !== $value && '' !== $value;
    }
}
