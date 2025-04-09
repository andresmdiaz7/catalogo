<?php
namespace App\Command;

use App\Service\SliderArchivoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:limpiar-archivos-slider',
    description: 'Limpia los archivos de sliders que no están referenciados en la base de datos',
)]
class LimpiarArchivosSliderCommand extends Command
{
    private $sliderArchivoService;

    public function __construct(SliderArchivoService $sliderArchivoService)
    {
        parent::__construct();
        $this->sliderArchivoService = $sliderArchivoService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Limpiando archivos de sliders no utilizados');
        
        $archivosEliminados = $this->sliderArchivoService->limpiarArchivosNoUtilizados();
        
        $io->success(sprintf('Se eliminaron %d archivos no utilizados', $archivosEliminados));

        return Command::SUCCESS;
    }
}
