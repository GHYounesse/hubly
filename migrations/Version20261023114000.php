<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Project follow-up: owner, lead developer, commercial contact, delivery
 * planning, priority and the validation checklist.
 */
final class Version20261023114000 extends AbstractMigration
{
    private const FOREIGN_KEY_COLUMNS = ['suivi_par_id', 'developpeur_principal_id', 'commercial_id'];

    public function getDescription(): string
    {
        return 'Project follow-up: people, delivery dates, priority and validation flags on projet';
    }

    public function up(Schema $schema): void
    {
        $projet = $schema->getTable('projet');

        // People
        $projet->addColumn('suivi_par_id', Types::INTEGER, ['notnull' => false]);
        $projet->addColumn('developpeur_principal_id', Types::INTEGER, ['notnull' => false]);
        $projet->addColumn('commercial_id', Types::INTEGER, ['notnull' => false]);
        $projet->addForeignKeyConstraint('app_user', ['suivi_par_id'], ['id']);
        $projet->addForeignKeyConstraint('developpeur', ['developpeur_principal_id'], ['id']);
        $projet->addForeignKeyConstraint('app_user', ['commercial_id'], ['id']);

        // Planning
        $projet->addColumn('remarques', Types::TEXT, ['notnull' => false]);
        $projet->addColumn('priorite', Types::STRING, ['length' => 20, 'default' => 'normal']);
        $projet->addColumn('date_commande', Types::DATE_MUTABLE, ['notnull' => false]);
        $projet->addColumn('date_livraison', Types::DATE_MUTABLE, ['notnull' => false]);
        $projet->addColumn('risque_livraison', Types::TEXT, ['notnull' => false]);

        // Validation checklist
        $projet->addColumn('projet_teste', Types::BOOLEAN, ['default' => false]);
        $projet->addColumn('projet_valide', Types::BOOLEAN, ['default' => false]);
        $projet->addColumn('client_gratuite', Types::BOOLEAN, ['default' => false]);
        $projet->addColumn('client_ameliorations', Types::BOOLEAN, ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        $projet = $schema->getTable('projet');

        $this->dropForeignKeysOn($projet, self::FOREIGN_KEY_COLUMNS);

        foreach ([
            'suivi_par_id', 'developpeur_principal_id', 'commercial_id',
            'remarques', 'priorite', 'date_commande', 'date_livraison', 'risque_livraison',
            'projet_teste', 'projet_valide', 'client_gratuite', 'client_ameliorations',
        ] as $column) {
            $projet->dropColumn($column);
        }
    }

    /** Removes the foreign keys (and their indexes) that use the given columns. */
    private function dropForeignKeysOn(Table $table, array $columns): void
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if (array_intersect($foreignKey->getLocalColumns(), $columns)) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }
        foreach ($table->getIndexes() as $index) {
            if (!$index->isPrimary() && array_intersect($index->getColumns(), $columns)) {
                $table->dropIndex($index->getName());
            }
        }
    }
}
