<?php

namespace App\Command;

use App\Repository\Mssql\ArticuloMssqlRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:probar-articulos-mssql',
    description: 'Prueba la obtención de artículos desde MSSQL',
)]
class ProbarArticulosMssqlCommand extends Command
{
    private ArticuloMssqlRepository $articuloMssqlRepository;

    public function __construct(ArticuloMssqlRepository $articuloMssqlRepository)
    {
        parent::__construct();
        $this->articuloMssqlRepository = $articuloMssqlRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Prueba de obtención de artículos desde MSSQL');

        $articulos = $this->articuloMssqlRepository->getAllArticulos();
        $io->info('Primeros 5 artículos obtenidos:');
        foreach (array_slice($articulos, 0, 5) as $articulo) {
            $io->writeln(print_r($articulo, true));
        }
        $io->success('Consulta realizada correctamente.');
        return Command::SUCCESS;
    }
} 