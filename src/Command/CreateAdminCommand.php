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
    name: 'app:create-admin',
    description: 'Crée l\'administrateur en base de données',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        /*
            - 'InputArgument'
                - 'OPTIONAL' optionel
                - 'REQUIRED' réquis
        */
        /*
            $this
                ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
                ->addArgument('nom', InputArgument::REQUIRED, 'Nom de l\'utilisateur')
                ->addArgument('prenom', InputArgument::REQUIRED, 'Prenom de l\'utilisateur')
                ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
                ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Rôle (ex: ROLE_ADMIN)', 'ROLE_SUPER_ADMIN')
            ;
        */
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /*
            $email = $input->getArgument('email');
            $nom = $input->getArgument('nom');
            $prenom = $input->getArgument('prenom');
            $plainPassword = $input->getArgument('password');
            $role = $input->getOption('role');

            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($existingUser) {
                $io->error('Un utilisateur avec cet email existe déjà');
                return Command::FAILURE;
            }
        */
        $user = new User();
        $user
            ->setNom('Admin')
            ->setPrenom('Damo')
            ->setEmail('admin@gmail.com')
            ->setRoles(['ROLE_SUPER_ADMIN']);

        $hashedPassword = $this->hasher->hashPassword($user, 'admin');
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();
        // $io->success(sprintf('Utilisateur %s créé avec le rôle %s', $email, $role));

        return Command::SUCCESS;
    }
} // php bin/console app:create-admin --email=..