<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reference data: client companies, technologies and hosting plans.
 */
final class Version20261023093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reference data: societe, user_societe, technologie and hebergement tables';
    }

    public function up(Schema $schema): void
    {
        $societe = $schema->createTable('societe');
        $societe->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $societe->addColumn('denomination', Types::STRING, ['length' => 255]);
        $societe->addColumn('statut', Types::STRING, ['length' => 50]);
        $societe->addColumn('created_at', Types::DATETIME_MUTABLE);
        $societe->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $societe->addColumn('adresse', Types::STRING, ['length' => 255, 'notnull' => false]);
        $societe->addColumn('email', Types::STRING, ['length' => 180, 'notnull' => false]);
        $societe->addColumn('phone', Types::STRING, ['length' => 50, 'notnull' => false]);
        $societe->addColumn('ice', Types::STRING, ['length' => 50, 'notnull' => false]);
        $societe->addColumn('logo', Types::STRING, ['length' => 255]);
        $societe->addColumn('description', Types::TEXT);
        $societe->setPrimaryKey(['id']);

        // Which users can see which companies
        $userSociete = $schema->createTable('user_societe');
        $userSociete->addColumn('user_id', Types::INTEGER);
        $userSociete->addColumn('societe_id', Types::INTEGER);
        $userSociete->setPrimaryKey(['user_id', 'societe_id']);
        $userSociete->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $userSociete->addForeignKeyConstraint('societe', ['societe_id'], ['id'], ['onDelete' => 'CASCADE']);

        $technologie = $schema->createTable('technologie');
        $technologie->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $technologie->addColumn('nom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $technologie->addColumn('version', Types::STRING, ['length' => 255, 'notnull' => false]);
        $technologie->addColumn('logo', Types::STRING, ['length' => 255, 'notnull' => false]);
        $technologie->addColumn('created', Types::DATETIME_MUTABLE);
        $technologie->addColumn('updated', Types::DATETIME_MUTABLE);
        $technologie->setPrimaryKey(['id']);

        $hebergement = $schema->createTable('hebergement');
        $hebergement->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $hebergement->addColumn('fournisseur', Types::STRING, ['length' => 255, 'notnull' => false]);
        $hebergement->addColumn('type', Types::STRING, ['length' => 255, 'notnull' => false]);
        $hebergement->addColumn('url', Types::STRING, ['length' => 255, 'notnull' => false]);
        $hebergement->addColumn('date_expiration', Types::DATE_MUTABLE, ['notnull' => false]);
        $hebergement->addColumn('est_actif', Types::BOOLEAN);
        $hebergement->addColumn('site', Types::STRING, ['length' => 255, 'notnull' => false]);
        $hebergement->addColumn('acces', Types::TEXT, ['notnull' => false]);
        $hebergement->addColumn('created', Types::DATETIME_MUTABLE);
        $hebergement->addColumn('updated', Types::DATETIME_MUTABLE);
        $hebergement->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('hebergement');
        $schema->dropTable('technologie');
        $schema->dropTable('user_societe');
        $schema->dropTable('societe');
    }
}
