<?php

namespace App\Command;

use App\Repository\CarritoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:limpiar-carritos-abandonados',
    description: 'Limpia los carritos abandonados después de cierto tiempo',
)]
class LimpiarCarritosAbandonadosCommand extends Command
{
    private $carritoRepository;
    private $entityManager;

    public function __construct(
        CarritoRepository $carritoRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->carritoRepository = $carritoRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addOption('dias', 'd', InputOption::VALUE_OPTIONAL, 'Días de antigüedad para considerar un carrito abandonado', 30)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dias = $input->getOption('dias');
        
        $fechaLimite = new \DateTime();
        $fechaLimite->modify("-$dias days");
        
        $io->note("Buscando carritos no modificados desde: " . $fechaLimite->format('Y-m-d H:i:s'));
        
        $carritosAbandonados = $this->carritoRepository->findCarritosAbandonados($fechaLimite);
        $count = count($carritosAbandonados);
        
        if ($count === 0) {
            $io->success('No hay carritos abandonados para limpiar.');
            return Command::SUCCESS;
        }
        
        $io->progressStart($count);
        
        foreach ($carritosAbandonados as $carrito) {
            $carrito->setEstado('abandonado');
            $io->progressAdvance();
        }
        
        $this->entityManager->flush();
        $io->progressFinish();
        
        $io->success("Se han marcado $count carritos como abandonados.");
        
        return Command::SUCCESS;
    }
}
