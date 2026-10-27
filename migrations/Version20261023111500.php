<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Two-factor authentication: store the e-mailed code and its expiry on the user.
 */
final class Version20261023111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Two-factor authentication: add two_factor_code and two_factor_expires_at to user';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->getTable('app_user');
        $user->addColumn('two_factor_code', Types::STRING, ['length' => 255, 'notnull' => false]);
        $user->addColumn('two_factor_expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $user = $schema->getTable('app_user');
        $user->dropColumn('two_factor_code');
        $user->dropColumn('two_factor_expires_at');
    }
}
