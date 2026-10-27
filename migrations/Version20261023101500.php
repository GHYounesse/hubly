<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Core of the application: developers, projects and their relations.
 */
final class Version20261023101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Core: developpeur and projet tables, plus their many-to-many relations';
    }

    public function up(Schema $schema): void
    {
        // One developer profile per user
        $developpeur = $schema->createTable('developpeur');
        $developpeur->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $developpeur->addColumn('nom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $developpeur->addColumn('prenom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $developpeur->addColumn('email', Types::STRING, ['length' => 255, 'notnull' => false]);
        $developpeur->addColumn('telephone', Types::STRING, ['length' => 255, 'notnull' => false]);
        $developpeur->addColumn('user_id', Types::INTEGER);
        $developpeur->addColumn('created', Types::DATETIME_MUTABLE);
        $developpeur->addColumn('updated', Types::DATETIME_MUTABLE);
        $developpeur->setPrimaryKey(['id']);
        $developpeur->addUniqueIndex(['user_id']);
        $developpeur->addForeignKeyConstraint('app_user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);

        $projet = $schema->createTable('projet');
        $projet->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $projet->addColumn('nom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $projet->addColumn('type', Types::STRING, ['length' => 255, 'notnull' => false]);
        $projet->addColumn('description', Types::TEXT, ['notnull' => false]);
        $projet->addColumn('acces_code_source', Types::TEXT, ['notnull' => false]);
        $projet->addColumn('acces_environnement', Types::TEXT, ['notnull' => false]);
        $projet->addColumn('status', Types::STRING, ['length' => 20, 'default' => 'à faire']);
        $projet->addColumn('url', Types::STRING, ['length' => 255, 'notnull' => false]);
        $projet->addColumn('societe_id', Types::INTEGER);
        $projet->addColumn('created', Types::DATETIME_MUTABLE);
        $projet->addColumn('updated', Types::DATETIME_MUTABLE);
        $projet->setPrimaryKey(['id']);
        $projet->addForeignKeyConstraint('societe', ['societe_id'], ['id'], ['onDelete' => 'CASCADE']);

        $this->joinTable($schema, 'projet_technologie', 'technologie');
        $this->joinTable($schema, 'projet_developpeur', 'developpeur');
        $this->joinTable($schema, 'projet_hebergement', 'hebergement');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('projet_hebergement');
        $schema->dropTable('projet_developpeur');
        $schema->dropTable('projet_technologie');
        $schema->dropTable('projet');
        $schema->dropTable('developpeur');
    }

    /** Creates the projet <-> $other many-to-many join table. */
    private function joinTable(Schema $schema, string $name, string $other): void
    {
        $table = $schema->createTable($name);
        $table->addColumn('projet_id', Types::INTEGER);
        $table->addColumn($other . '_id', Types::INTEGER);
        $table->setPrimaryKey(['projet_id', $other . '_id']);
        $table->addForeignKeyConstraint('projet', ['projet_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint($other, [$other . '_id'], ['id'], ['onDelete' => 'CASCADE']);
    }
}
