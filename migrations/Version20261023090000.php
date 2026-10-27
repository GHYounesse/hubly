<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Authentication: application users and password reset requests.
 *
 * Written with the DBAL Schema API (not raw SQL) for PostgreSQL,
 * and so index / foreign key names are generated exactly like the ORM does.
 */
final class Version20261023090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Authentication: user and reset_password_request tables';
    }

    public function up(Schema $schema): void
    {
        $user = $schema->createTable('app_user');
        $user->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $user->addColumn('email', Types::STRING, ['length' => 180]);
        $user->addColumn('roles', Types::JSON);
        $user->addColumn('password', Types::STRING, ['length' => 255]);
        $user->addColumn('statut', Types::STRING, ['length' => 255]);
        $user->addColumn('nom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $user->addColumn('prenom', Types::STRING, ['length' => 255, 'notnull' => false]);
        $user->addColumn('created', Types::DATETIME_MUTABLE);
        $user->addColumn('updated', Types::DATETIME_MUTABLE);
        $user->setPrimaryKey(['id']);
        $user->addUniqueIndex(['email'], 'UNIQ_IDENTIFIER_EMAIL');

        $reset = $schema->createTable('reset_password_request');
        $reset->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $reset->addColumn('user_id', Types::INTEGER);
        $reset->addColumn('selector', Types::STRING, ['length' => 20]);
        $reset->addColumn('hashed_token', Types::STRING, ['length' => 100]);
        $reset->addColumn('requested_at', Types::DATETIME_IMMUTABLE);
        $reset->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $reset->setPrimaryKey(['id']);
        $reset->addForeignKeyConstraint('app_user', ['user_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('reset_password_request');
        $schema->dropTable('app_user');
    }
}
