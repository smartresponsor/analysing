<?php

declare(strict_types=1);

use App\Analysing\Migrations\Version20260914083153;
use App\Analysing\Migrations\Version20261004075500;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\NullLogger;

require dirname(__DIR__, 2).'/vendor/autoload.php';
require_once dirname(__DIR__, 2).'/migrations/Version20260914083153.php';
require_once dirname(__DIR__, 2).'/migrations/Version20261004075500.php';

$connection = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'memory' => true,
]);
$logger = new NullLogger();

/** @var list<AbstractMigration> $migrations */
$migrations = [
    new Version20260914083153($connection, $logger),
    new Version20261004075500($connection, $logger),
];

foreach ($migrations as $migration) {
    $migration->up($connection->createSchemaManager()->introspectSchema());
    foreach ($migration->getSql() as $query) {
        $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
    }
}

$configuration = ORMSetup::createAttributeMetadataConfig(
    paths: [dirname(__DIR__, 2).'/src/Entity'],
    isDevMode: true,
);
$configuration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER, true));
$configuration->enableNativeLazyObjects(true);
$entityManager = new EntityManager($connection, $configuration);
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool = new SchemaTool($entityManager);
$pendingSql = array_values($schemaTool->getUpdateSchemaSql($metadata));

if ([] !== $pendingSql) {
    fwrite(STDERR, "Analytics migration/schema parity failed.\n");
    fwrite(STDERR, sprintf("Pending SQL count: %d\n", count($pendingSql)));
    foreach ($pendingSql as $sql) {
        fwrite(STDERR, '  '.$sql.PHP_EOL);
    }

    exit(1);
}

$expectedTables = array_map(
    static fn (\Doctrine\ORM\Mapping\ClassMetadata $classMetadata): string => $classMetadata->getTableName(),
    $metadata,
);
sort($expectedTables);
$actualTables = array_map('strtolower', $connection->createSchemaManager()->listTableNames());
sort($actualTables);

if ($expectedTables !== $actualTables) {
    fwrite(STDERR, "Analytics migration/schema table inventory mismatch.\n");
    fwrite(STDERR, 'Expected: '.implode(', ', $expectedTables).PHP_EOL);
    fwrite(STDERR, 'Actual: '.implode(', ', $actualTables).PHP_EOL);

    exit(1);
}

fwrite(STDOUT, sprintf("Analytics migration/schema parity: OK (%d tables, %d migrations).\n", count($actualTables), count($migrations)));
