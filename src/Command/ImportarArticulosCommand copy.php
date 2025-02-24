<?php

namespace App\Command;

use App\Entity\Articulo;
use App\Entity\Rubro;
use App\Entity\Subrubro;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use League\Csv\Statement;

#[AsCommand(
    name: 'app:importar-articulos',
    description: 'Importa artículos desde un archivo CSV',
)]

class ImportarArticulosCommand extends Command
{
    private ManagerRegistry $doctrine;
    private string $projectDir;
    private const LOTE_TAMANIO = 10000;


    public function __construct(ManagerRegistry $doctrine, string $projectDir)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->projectDir = $projectDir;
       
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rutaArchivo = $this->projectDir . '/imports/ART_WEB_L1_V2.CSV';

        if (!$rutaArchivo) {
            $io->error(sprintf('No se pudo abrir el archivo: %s', $rutaArchivo));
            return Command::FAILURE;
        }
        

        $encoder = (new CharsetConverter())
            ->inputEncoding('iso-8859-1')
            ->outputEncoding('UTF-8');

        $csv = Reader::createFromPath($rutaArchivo, 'r');
        $csv->addFormatter($encoder);
        $csv->setDelimiter(';');

        $stmt = Statement::create()
            //->select('codigo', 'detalle', 'marca')
            //->andWhere('marca', 'starts with', 'SIXELECTRIC')
            //->orderByAsc('codigo')
            //->andWhere('habilitado', '=', '1')
            ->offset(0);
            //->limit(3);
        
        $records = $stmt->process($csv);
        //$records = $csv->getRecords();

        $io->info('Iniciando importación de artículos...');
        
        try {
            
            $lote = [];
            $contador = 0;

            foreach ($records as $linea) {
                
                try {
                    $lote[] = $this->crearArticulo($linea);
                    next($records);
                    $contador++;

                    if (count($lote) >= self::LOTE_TAMANIO) {
                        $this->procesarLote($lote, $io);
                        $lote = [];
                    }
                } catch (\Exception $e) {
                    $io->error(sprintf(
                        "Error al procesar línea %d: %s",
                        $contador,
                        $e->getMessage()
                    ));
                }
            }

            // Procesar el último lote si existe
            if (!empty($lote)) {
                $this->procesarLote($lote, $io);
            }

            $io->success(sprintf('Importación completada. Se importaron %d artículos.', $contador));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error durante la importación: ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            $this->doctrine->getManager()->clear();
            
        }
    }

    private function crearArticulo(array $linea): Articulo
    {   
        // Buscar o crear el rubro
        $rubroCodigo = $linea[2];
        $rubro = $this->doctrine->getRepository(Rubro::class)->findOneBy(['codigo' => $rubroCodigo]);
        
        if (!$rubro) {
            $rubro = new Rubro();
            $rubro->setCodigo($rubroCodigo);
            $rubro->setNombre($linea[3]);
            $this->doctrine->getManager()->persist($rubro);
        }

        // Buscar o crear el subrubro
        $subrubroCodigo = $linea[4]; 
        $subrubroNombre = $linea[5]; 
        $subrubro = $this->doctrine->getRepository(Subrubro::class)->findOneBy([
            'codigo' => $subrubroCodigo
        ]);

        if (!$subrubro) {
            $subrubro = new Subrubro();
            $subrubro->setCodigo($subrubroCodigo);
            $subrubro->setNombre($subrubroNombre);
            $subrubro->setRubro($rubro);
            $this->doctrine->getManager()->persist($subrubro);
            $this->doctrine->getManager()->flush();
        }
        
        $articulo = new Articulo();
        $articulo->setCodigo($linea[0]);
        $articulo->setDetalle($linea[1]);
        $articulo->setMarca($linea[7]);
        $articulo->setModelo($linea[12]);
        $articulo->setDetalleWeb($linea[13]);
        $articulo->setImpuesto($linea[8]);
        $articulo->setPrecioLista($linea[9]);
        $articulo->setPrecio400($linea[9]);
        $articulo->setDestacado(false);
        $articulo->setHabilitadoWeb(true);
        $articulo->setHabilitadoGestion($linea[5]);
        $articulo->setNovedad(false);
        $articulo->setSubrubro($subrubro);
        // ... configurar demás campos según la estructura de tu CSV
        
        return $articulo;
    }

    private function procesarLote(array $articulos, SymfonyStyle $io): void
    {
        $em = $this->doctrine->getManager();

        foreach ($articulos as $articulo) {
            $em->persist($articulo);
        }

        try {
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $io->info(sprintf('Procesados %d artículos...', count($articulos)));
        } catch (\Exception $e) {
            $io->error('Error al guardar el lote: ' . $e->getMessage());
            throw $e;
        } finally {
            
        }
    }
} 