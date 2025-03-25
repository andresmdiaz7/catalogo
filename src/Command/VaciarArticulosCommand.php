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
            $conn = $em->getConnection();

            // Preguntar si también quiere borrar rubros y subrubros
            $borrarTodo = $io->confirm(
                '¿Desea borrar también los rubros, subrubros y marcas?',
                false
            );

            // Desactivamos temporalmente las restricciones de clave foránea
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            
            // Eliminar las relaciones en la tabla pivote
            $conn->executeStatement('DELETE FROM articulo_archivo');
            $io->info('Se han eliminado las relaciones entre artículos y archivos.');

            if ($borrarTodo) {
                // Borrar artículos 
                $articulosEliminados = $em->createQuery('DELETE FROM App\Entity\Articulo a')
                    ->execute();

                // Borrar subrubros
                $subrubrosEliminados = $em->createQuery('DELETE FROM App\Entity\Subrubro s')
                    ->execute();

                // Borrar rubros
                $rubrosEliminados = $em->createQuery('DELETE FROM App\Entity\Rubro r')
                    ->execute();
                
                // Borrar marcas
                $marcasEliminadas = $em->createQuery('DELETE FROM App\Entity\Marca m')
                    ->execute();

                $io->success(sprintf(
                    'Se han eliminado: %d artículos, %d subrubros, %d rubros y %d marcas',
                    $articulosEliminados,
                    $subrubrosEliminados,
                    $rubrosEliminados,
                    $marcasEliminadas
                ));
            } else {
                // Solo borrar artículos
                $articulosEliminados = $em->createQuery('DELETE FROM App\Entity\Articulo a')
                    ->execute();

                $io->success(sprintf('Se han eliminado %d artículos', $articulosEliminados));
            }
            
            // Restauramos las restricciones de clave foránea
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error al vaciar las tablas: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}