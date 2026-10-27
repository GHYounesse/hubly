<?php

namespace App\Command;

use App\Service\Monitoring\SslMonitor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-ssl',
    description: 'Vérifie la date d\'expiration du certificat SSL de chaque projet',
)]
class CheckSslCommand extends Command
{
    public function __construct(private readonly SslMonitor $sslMonitor)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $warnings = $this->sslMonitor->checkAll();
        $output->writeln(sprintf('Certificats à renouveler (moins de %d jours) : %d', SslMonitor::WARNING_DAYS, $warnings));

        return Command::SUCCESS;
    }
}
