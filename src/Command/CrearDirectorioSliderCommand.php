<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:crear-directorios-uploads',
    description: 'Crea los directorios necesarios para subir archivos',
)]
class CrearDirectorioSliderCommand extends Command
{
    private $sliderDirectory;
    private $archivosDirectory;
    private $filesystem;
    private $projectDir;

    public function __construct(
        string $sliderDirectory, 
        string $archivosDirectory,
        Filesystem $filesystem,
        ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
        $this->sliderDirectory = $sliderDirectory;
        $this->archivosDirectory = $archivosDirectory;
        $this->filesystem = $filesystem;
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Crear directorio de sliders
        if (!$this->filesystem->exists($this->sliderDirectory)) {
            $this->filesystem->mkdir($this->sliderDirectory, 0755);
            $io->success(sprintf('Directorio de sliders creado: %s', $this->sliderDirectory));
        } else {
            $io->info(sprintf('El directorio de sliders ya existe: %s', $this->sliderDirectory));
        }

        // Crear directorio de archivos
        if (!$this->filesystem->exists($this->archivosDirectory)) {
            $this->filesystem->mkdir($this->archivosDirectory, 0755);
            $io->success(sprintf('Directorio de archivos creado: %s', $this->archivosDirectory));
        } else {
            $io->info(sprintf('El directorio de archivos ya existe: %s', $this->archivosDirectory));
        }

        // Crear subdirectorios del 0 al 99 para archivos
        for ($i = 0; $i < 100; $i++) {
            $subDir = $this->archivosDirectory . '/' . $i;
            if (!$this->filesystem->exists($subDir)) {
                $this->filesystem->mkdir($subDir, 0755);
                $io->text(sprintf('Subdirectorio creado: %s', $subDir));
            }
        }

        // Crear directorio alternativo en var/uploads
        $alternativeDir = $this->projectDir . '/var/uploads/archivos';
        if (!$this->filesystem->exists($alternativeDir)) {
            $this->filesystem->mkdir($alternativeDir, 0755);
            $io->success(sprintf('Directorio alternativo creado: %s', $alternativeDir));
        }

        $io->success('Todos los directorios de uploads han sido creados correctamente.');
        return Command::SUCCESS;
    }
}
