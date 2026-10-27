<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Indexes for the columns the dashboard, the alerts and the list filters query on.
 */
final class Version20261024103000 extends AbstractMigration
{
    private const INDEXES = [
        ['projet', 'idx_projet_status', ['status']],
        ['hebergement', 'idx_hebergement_date_expiration', ['date_expiration']],
        ['notification', 'idx_notification_is_read', ['is_read']],
        ['historique', 'idx_historique_date_modification', ['date_modification']],
    ];

    public function getDescription(): string
    {
        return 'Performance: indexes on projet.status, hebergement.date_expiration, notification.is_read, historique.date_modification';
    }

    public function up(Schema $schema): void
    {
        foreach (self::INDEXES as [$table, $name, $columns]) {
            $schema->getTable($table)->addIndex($columns, $name);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::INDEXES as [$table, $name]) {
            $schema->getTable($table)->dropIndex($name);
        }
    }
}
