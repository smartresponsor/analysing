<?php

declare(strict_types=1);

namespace App\Analysing\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004075500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename legacy alert_log camelCase columns to canonical snake_case identifiers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert_log RENAME COLUMN vendorId TO vendor_id');
        $this->addSql('ALTER TABLE alert_log RENAME COLUMN createdAt TO created_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert_log RENAME COLUMN vendor_id TO vendorId');
        $this->addSql('ALTER TABLE alert_log RENAME COLUMN created_at TO createdAt');
    }

    public function isTransactional(): bool
    {
        return true;
    }
}
