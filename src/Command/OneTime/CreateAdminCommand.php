<?php

namespace App\Command\OneTime;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
    public function __construct (
        private readonly UserRepository $userRepository, 
        private readonly UserPasswordHasherInterface $passwordEncoder
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Создаем юзера admin')
            ->setHelp('Создаем юзера admin c паролем 123456');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->userRepository->findOneBy(['login' => 'admin']);

        if ($admin instanceof User) {
            $output->writeln('Учетная запись admin уже существует!');
            return Command::FAILURE;
        }

        $output->writeln('Создаем пользователя...');
        $admin = new User();
        
        $admin
            ->setLogin('admin')
            ->setFirstName('admin')
            ->setLastName('admin')
            ->setFullName('admin')
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
            ->setPassword($this->passwordEncoder->hashPassword($admin, '123456'))
            ->setRoles([User::ROLE_SUPER_ADMIN]);

        $this->userRepository->save($admin, true);

        $output->writeln('Учетная запись admin успешно создана.');
        return Command::SUCCESS;
    }
}
