<?php

namespace App\Command;

use App\Entity\ArticuloArchivo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:importar-imagenes',
    description: 'Importa imágenes desde CSV a la entidad ArticuloArchivo',
)]
class ImportarImagenesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvFile = 'imports/imagenes.csv';

        if (!file_exists($csvFile)) {
            $io->error('El archivo imports/imagenes.csv no existe');
            return Command::FAILURE;
        }

        $file = fopen($csvFile, 'r');
        $count = 0;
        $errors = [];

        while (($data = fgetcsv($file, 0, ';', '"')) !== false) {
            if (count($data) !== 2) {
                $errors[] = "Línea inválida: " . implode(';', $data);
                continue;
            }

            $codigoArticulo = trim($data[0]);
            $rutaArchivo = trim($data[1]);

            $articulo = $this->entityManager->getRepository('App\Entity\Articulo')
                ->findOneBy(['codigo' => $codigoArticulo]);

            if (!$articulo) {
                $errors[] = "Artículo no encontrado: {$codigoArticulo}";
                continue;
            }

            $archivo = new ArticuloArchivo();
            $archivo->setArticulo($articulo)
                   ->setRutaArchivo($rutaArchivo)
                   ->setTipoArchivo('imagen')
                   ->setEsPrincipal(true);

            $this->entityManager->persist($archivo);
            $count++;

            if ($count % 5000 === 0) {
                $this->entityManager->flush();
                $io->info("Procesados {$count} registros...");
            }
        }

        fclose($file);
        $this->entityManager->flush();

        if (!empty($errors)) {
            $io->warning('Se encontraron los siguientes errores:');
            foreach ($errors as $error) {
                $io->text($error);
            }
        }

        $io->success("Importación completada. Se importaron {$count} imágenes.");
        return Command::SUCCESS;
    }
} 