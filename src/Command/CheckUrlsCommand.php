<?php
namespace App\Command;

use App\Service\Monitoring\UrlService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-urls',
    description: 'Vérifie les URLs des projets',
)]
class CheckUrlsCommand extends Command
{
    public function __construct(private readonly UrlService $urlService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = $this->urlService->checkUrls();

        if (empty($errors)) {
            $output->writeln('<info>Toutes les URLs sont valides.</info>');
        } else {
            $output->writeln('<error>URLs injoignables détectées :</error>');
            foreach ($errors as $url) {
                $output->writeln("- $url");
            }
        }

        return Command::SUCCESS;
    }
}
