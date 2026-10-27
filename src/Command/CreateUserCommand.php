<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un utilisateur (il n\'existe pas d\'inscription publique)',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail (identifiant de connexion)')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Donne le rôle ROLE_ADMIN')
            ->addOption('nom', null, InputOption::VALUE_REQUIRED, 'Nom')
            ->addOption('prenom', null, InputOption::VALUE_REQUIRED, 'Prénom');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        if ($this->entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un utilisateur avec l\'adresse %s existe déjà.', $email));

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($input->getOption('admin') ? ['ROLE_ADMIN'] : ['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $input->getArgument('password')));
        $user->setStatut('actif');
        $user->setNom($input->getOption('nom'));
        $user->setPrenom($input->getOption('prenom'));

        $now = new \DateTime();
        $user->setCreated($now);
        $user->setUpdated($now);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur %s créé (%s).', $email, $input->getOption('admin') ? 'ROLE_ADMIN' : 'ROLE_USER'));

        return Command::SUCCESS;
    }
}
