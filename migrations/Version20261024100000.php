<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hosting costs: price and billing period, to forecast the yearly renewal budget.
 */
final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hosting costs: add prix and periodicite to hebergement';
    }

    public function up(Schema $schema): void
    {
        $hebergement = $schema->getTable('hebergement');
        $hebergement->addColumn('prix', Types::DECIMAL, ['precision' => 10, 'scale' => 2, 'notnull' => false]);
        $hebergement->addColumn('periodicite', Types::STRING, ['length' => 10, 'default' => 'annuel']);
    }

    public function down(Schema $schema): void
    {
        $hebergement = $schema->getTable('hebergement');
        $hebergement->dropColumn('prix');
        $hebergement->dropColumn('periodicite');
    }
}
