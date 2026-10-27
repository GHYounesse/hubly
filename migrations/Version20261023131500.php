<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Field-level audit trail: store the before / after value of each changed field.
 */
final class Version20261023131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Audit trail: add the changes column (before / after values) to historique';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('historique')->addColumn('changes', Types::JSON, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('historique')->dropColumn('changes');
    }
}
