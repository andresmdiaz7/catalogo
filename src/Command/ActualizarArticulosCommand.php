<?php

namespace App\Command;

use App\Entity\Articulo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:actualizar-articulos',
    description: 'Actualiza artículos desde un archivo CSV',
)]
class ActualizarArticulosCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $archivo = fopen('imports/actualizacion_articulos.csv', 'r');
        
        if (!$archivo) {
            $io->error('No se pudo abrir el archivo CSV');
            return Command::FAILURE;
        }

        $cabecera = fgetcsv($archivo);
        $articulosRepository = $this->entityManager->getRepository(Articulo::class);
        
        while (($linea = fgetcsv($archivo)) !== false) {
            $articulo = $articulosRepository->find($linea[0]);
            
            if ($articulo) {
                $articulo->setDetalle($linea[1]);
                // ... actualizar demás campos
                $this->entityManager->persist($articulo);
            }
        }
        
        $this->entityManager->flush();
        fclose($archivo);

        $io->success('Artículos actualizados exitosamente');
        return Command::SUCCESS;
    }
} 