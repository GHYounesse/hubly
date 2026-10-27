<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Messenger Doctrine transport (async e-mails). Its automatic setup is disabled
 * in .env (`auto_setup=0`), so the table is created here, together with the
 * PostgreSQL LISTEN/NOTIFY trigger used by `use_notify: true` in messenger.yaml.
 */
final class Version20261023120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Async mail queue: messenger_messages table';
    }

    public function up(Schema $schema): void
    {
        $messages = $schema->createTable('messenger_messages');
        $messages->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $messages->addColumn('body', Types::TEXT);
        $messages->addColumn('headers', Types::TEXT);
        $messages->addColumn('queue_name', Types::STRING, ['length' => 190]);
        $messages->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $messages->addColumn('available_at', Types::DATETIME_IMMUTABLE);
        $messages->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $messages->setPrimaryKey(['id']);
        // Same table definition as Symfony's Doctrine transport: immutable dates and one composite index
        $messages->addIndex(['queue_name', 'available_at', 'delivered_at', 'id'], 'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeStatement(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
            SQL);
        $this->connection->executeStatement('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages');
        $this->connection->executeStatement('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages()');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('messenger_messages');
    }

    public function postDown(Schema $schema): void
    {
        $this->connection->executeStatement('DROP FUNCTION IF EXISTS notify_messenger_messages()');
    }
}
