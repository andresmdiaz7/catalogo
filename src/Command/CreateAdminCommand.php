<?php

namespace App\Command;

use App\Entity\Usuario;
use App\Entity\TipoUsuario;
use App\Repository\TipoUsuarioRepository;
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
    description: 'Crea un usuario administrador con tipo de usuario'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private TipoUsuarioRepository $tipoUsuarioRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email del administrador', 'admin@example.com')
            ->addArgument('password', InputArgument::OPTIONAL, 'Contraseña del administrador', '1234')
            ->addOption('nombre', null, InputOption::VALUE_OPTIONAL, 'Nombre del administrador', 'Admin')
            ->addOption('apellido', null, InputOption::VALUE_OPTIONAL, 'Apellido del administrador', 'Sistema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $nombre = $input->getOption('nombre');
        $apellido = $input->getOption('apellido');

        // Verificar si ya existe un usuario con este email
        $existingUser = $this->entityManager->getRepository(Usuario::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("Ya existe un usuario con el email: $email");
            return Command::FAILURE;
        }

        // Buscar o crear el tipo de usuario administrador
        $tipoUsuario = $this->tipoUsuarioRepository->findByCodigo('admin');
        if (!$tipoUsuario) {
            $io->note('Creando tipo de usuario admin ya que no existe...');
            $tipoUsuario = new TipoUsuario();
            $tipoUsuario->setCodigo('admin');
            $tipoUsuario->setNombre('Administrador');
            $tipoUsuario->setDescripcion('Usuario con acceso completo al sistema');
            $tipoUsuario->setActivo(true);
            $this->entityManager->persist($tipoUsuario);
            $this->entityManager->flush();
        }

        // Crear el usuario administrador
        $usuario = new Usuario();
        $usuario->setEmail($email);
        $usuario->setRoles(['ROLE_ADMIN']);
        $usuario->setNombre($nombre);
        $usuario->setApellido($apellido);
        $usuario->setTipoUsuario($tipoUsuario);
        $usuario->setActivo(true);
        
        // Hash de la contraseña
        $hashedPassword = $this->passwordHasher->hashPassword($usuario, $password);
        $usuario->setPassword($hashedPassword);

        $this->entityManager->persist($usuario);
        $this->entityManager->flush();

        $io->success("Usuario administrador creado exitosamente con email: $email");
        $io->table(
            ['Email', 'Nombre', 'Tipo', 'Roles'],
            [[$usuario->getEmail(), $usuario->getNombreCompleto(), $tipoUsuario->getNombre(), implode(', ', $usuario->getRoles())]]
        );

        return Command::SUCCESS;
    }
} 