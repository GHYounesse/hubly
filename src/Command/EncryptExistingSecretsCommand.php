<?php

namespace App\Command;

use App\Security\FieldEncryptor;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:encryption:encrypt-existing',
    description: 'Encrypts the credentials that were stored in plain text before encryption was enabled',
)]
class EncryptExistingSecretsCommand extends Command
{
    /** table => columns holding secrets */
    private const COLUMNS = [
        'hebergement' => ['acces'],
        'projet' => ['acces_code_source', 'acces_environnement'],
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%app.encryption_key%')] private readonly string $encryptionKey,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $encryptor = new FieldEncryptor($this->encryptionKey);
        $total = 0;

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $rows = $this->connection->fetchAllAssociative(
                    sprintf('SELECT id, %1$s AS secret FROM %2$s WHERE %1$s IS NOT NULL AND %1$s <> \'\' AND %1$s NOT LIKE ?', $column, $table),
                    [FieldEncryptor::PREFIX . '%'],
                );

                foreach ($rows as $row) {
                    $this->connection->update($table, [$column => $encryptor->encrypt($row['secret'])], ['id' => $row['id']]);
                }

                $io->writeln(sprintf('%s.%s: %d value(s) encrypted', $table, $column, count($rows)));
                $total += count($rows);
            }
        }

        $io->success($total === 0 ? 'Nothing to encrypt, everything is already protected.' : sprintf('%d value(s) encrypted.', $total));

        return Command::SUCCESS;
    }
}
