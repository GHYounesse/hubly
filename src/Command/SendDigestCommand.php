<?php

namespace App\Command;

use App\Service\Monitoring\DigestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-digest',
    description: 'Envoie le résumé du jour (hébergements, certificats, sites hors ligne)',
)]
class SendDigestCommand extends Command
{
    public function __construct(private readonly DigestService $digest)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->digest->send() ? 'Résumé envoyé.' : 'Rien à signaler, aucun résumé envoyé.');

        return Command::SUCCESS;
    }
}
