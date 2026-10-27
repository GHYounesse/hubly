<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Uptime history: one row per availability check of a project URL.
 */
final class Version20261023124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Uptime history: uptime_check table';
    }

    public function up(Schema $schema): void
    {
        $check = $schema->createTable('uptime_check');
        $check->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $check->addColumn('projet_id', Types::INTEGER);
        $check->addColumn('checked_at', Types::DATETIME_IMMUTABLE);
        $check->addColumn('status_code', Types::INTEGER, ['notnull' => false]);
        $check->addColumn('response_time_ms', Types::INTEGER, ['notnull' => false]);
        $check->addColumn('success', Types::BOOLEAN);
        $check->addColumn('error', Types::STRING, ['length' => 255, 'notnull' => false]);
        $check->setPrimaryKey(['id']);
        // Added before the foreign key so it also serves as the index of projet_id
        $check->addIndex(['projet_id', 'checked_at'], 'idx_uptime_projet_checked');
        $check->addForeignKeyConstraint('projet', ['projet_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('uptime_check');
    }
}
