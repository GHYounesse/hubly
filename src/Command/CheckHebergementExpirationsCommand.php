<?php
namespace App\Command;

use App\Service\Monitoring\HebergementExpirationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-expirations',
    description: 'Vérifie les notifications hébergements',
)]
class CheckHebergementExpirationsCommand extends Command
{
    private HebergementExpirationService $hebergementExpirationService;

    public function __construct(HebergementExpirationService $hebergementExpirationService)
    {
        parent::__construct();
        $this->hebergementExpirationService = $hebergementExpirationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notifications = $this->hebergementExpirationService->checkExpirationsAndNotify();

        $output->writeln(sprintf('Nombre de notifications créées : %d', count($notifications)));

        return Command::SUCCESS;
    }
}
