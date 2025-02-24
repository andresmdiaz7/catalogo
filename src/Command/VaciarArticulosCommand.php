<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;

#[AsCommand(
    name: 'app:vaciar-articulos',
    description: 'Elimina todos los artículos de la base de datos',
)]
class VaciarArticulosCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $doctrine;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $doctrine)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setName('app:vaciar-articulos')
            ->setDescription('Vacía la tabla de artículos');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $em = $this->doctrine->getManager();

            // Preguntar si también quiere borrar rubros y subrubros
            $borrarTodo = $io->confirm(
                '¿Desea borrar también los rubros y subrubros?',
                false
            );

            if ($borrarTodo) {
                // Borrar artículos primero debido a las restricciones de clave foránea
                $articulosEliminados = $em->createQuery('DELETE FROM App\Entity\Articulo a')
                    ->execute();

                // Borrar subrubros
                $subrubrosEliminados = $em->createQuery('DELETE FROM App\Entity\Subrubro s')
                    ->execute();

                // Borrar rubros
                $rubrosEliminados = $em->createQuery('DELETE FROM App\Entity\Rubro r')
                    ->execute();

                $io->success(sprintf(
                    'Se han eliminado: %d artículos, %d subrubros y %d rubros',
                    $articulosEliminados,
                    $subrubrosEliminados,
                    $rubrosEliminados
                ));
            } else {
                // Solo borrar artículos
                $articulosEliminados = $em->createQuery('DELETE FROM App\Entity\Articulo a')
                    ->execute();

                $io->success(sprintf('Se han eliminado %d artículos', $articulosEliminados));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al vaciar las tablas: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 