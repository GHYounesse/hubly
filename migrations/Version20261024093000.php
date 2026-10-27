<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Certificate monitoring: when the TLS certificate of a project URL expires.
 */
final class Version20261024093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Certificate monitoring: add ssl_expires_at and ssl_checked_at to projet';
    }

    public function up(Schema $schema): void
    {
        $projet = $schema->getTable('projet');
        $projet->addColumn('ssl_expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $projet->addColumn('ssl_checked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $projet = $schema->getTable('projet');
        $projet->dropColumn('ssl_expires_at');
        $projet->dropColumn('ssl_checked_at');
    }
}
