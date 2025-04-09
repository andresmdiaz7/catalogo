<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:crear-directorio-slider',
    description: 'Crea el directorio para los archivos de sliders si no existe',
)]
class CrearDirectorioSliderCommand extends Command
{
    private $sliderDirectory;
    private $filesystem;

    public function __construct(string $sliderDirectory, Filesystem $filesystem)
    {
        parent::__construct();
        $this->sliderDirectory = $sliderDirectory;
        $this->filesystem = $filesystem;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->filesystem->exists($this->sliderDirectory)) {
            $this->filesystem->mkdir($this->sliderDirectory, 0777);
            $io->success(sprintf('Directorio creado: %s', $this->sliderDirectory));
        } else {
            $io->info(sprintf('El directorio ya existe: %s', $this->sliderDirectory));
        }

        return Command::SUCCESS;
    }
}
