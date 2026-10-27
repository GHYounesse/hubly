<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alerts (hosting expiration, URL down) and the audit trail.
 */
final class Version20261023104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alerts and audit: notification and historique tables';
    }

    public function up(Schema $schema): void
    {
        $notification = $schema->createTable('notification');
        $notification->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $notification->addColumn('title', Types::STRING, ['length' => 255]);
        $notification->addColumn('message', Types::TEXT);
        $notification->addColumn('created_at', Types::DATETIME_MUTABLE);
        $notification->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $notification->addColumn('is_read', Types::BOOLEAN);
        $notification->addColumn('hebergement_id', Types::INTEGER, ['notnull' => false]);
        $notification->addColumn('projet_id', Types::INTEGER, ['notnull' => false]);
        $notification->setPrimaryKey(['id']);
        $notification->addForeignKeyConstraint('hebergement', ['hebergement_id'], ['id']);
        $notification->addForeignKeyConstraint('projet', ['projet_id'], ['id']);

        $historique = $schema->createTable('historique');
        $historique->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $historique->addColumn('date_modification', Types::DATETIME_MUTABLE);
        $historique->addColumn('entite_modifiee', Types::STRING, ['length' => 255]);
        $historique->addColumn('action', Types::STRING, ['length' => 50]);
        $historique->addColumn('user_id', Types::INTEGER);
        $historique->addColumn('entite_modifiee_id', Types::INTEGER, ['notnull' => false]);
        $historique->setPrimaryKey(['id']);
        $historique->addForeignKeyConstraint('app_user', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('historique');
        $schema->dropTable('notification');
    }
}
