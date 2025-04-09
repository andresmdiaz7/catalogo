<?php

namespace App\Command;

use App\Repository\ArchivoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:limpiar-archivos-vacios',
    description: 'Elimina todos los archivos de la entidad Archivo que tengan el campo file_path vacío',
)]
class LimpiarArchivosVaciosCommand extends Command
{
    private $archivoRepository;
    private $entityManager;

    public function __construct(ArchivoRepository $archivoRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->archivoRepository = $archivoRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Limpiando archivos con file_path vacío');

        // Buscar archivos con file_path vacío
        $archivos = $this->archivoRepository->findBy(['file_path' => '']);

        if (count($archivos) === 0) {
            $io->success('No se encontraron archivos con file_path vacío.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Se encontraron %d archivos con file_path vacío.', count($archivos)));

        // Eliminar los archivos
        $count = 0;
        foreach ($archivos as $archivo) {
            $this->entityManager->remove($archivo);
            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Se eliminaron %d archivos con file_path vacío.', $count));

        return Command::SUCCESS;
    }
}
