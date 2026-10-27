<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Credentials at rest: the access columns become "encrypted_text".
 * The SQL type stays TEXT, so existing rows are untouched; run
 * `php bin/console app:encryption:encrypt-existing` to encrypt the values already stored.
 */
final class Version20261024090000 extends AbstractMigration
{
    private const COLUMNS = [
        'hebergement' => ['acces'],
        'projet' => ['acces_code_source', 'acces_environnement'],
    ];

    public function getDescription(): string
    {
        return 'Security: hosting and project access columns use the encrypted_text type';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $schema->getTable($table)->modifyColumn($column, ['type' => Type::getType('encrypted_text')]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $schema->getTable($table)->modifyColumn($column, ['type' => Type::getType(Types::TEXT)]);
            }
        }
    }
}
