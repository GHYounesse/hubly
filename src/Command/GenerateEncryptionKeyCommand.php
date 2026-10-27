<?php

namespace App\Command;

use App\Security\FieldEncryptor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:encryption:generate-key',
    description: 'Prints a new random APP_ENCRYPTION_KEY',
)]
class GenerateEncryptionKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('APP_ENCRYPTION_KEY=' . FieldEncryptor::generateKey());
        $io->note('Put this line in your .env (or your hosting secrets). Losing the key makes the encrypted credentials unrecoverable.');

        return Command::SUCCESS;
    }
}
