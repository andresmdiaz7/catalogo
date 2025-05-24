<?php

namespace App\Command;

use App\Entity\Articulo;
use App\Entity\Marca;
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
use Doctrine\ORM\EntityManager;

#[AsCommand(
    name: 'app:importar-articulos',
    description: 'Importa artículos desde un archivo CSV',
)]

class ImportarArticulosCommand extends Command
{
    private ManagerRegistry $doctrine;
    private string $projectDir;
    private const LOTE_TAMANIO = 5000;
    private array $rubrosCache = [];
    private array $subrubrosCache = [];
    private array $marcasCache = []; // Caché para marcas

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

        if (!file_exists($rutaArchivo)) {
            $io->error(sprintf('No se pudo abrir el archivo: %s', $rutaArchivo));
            return Command::FAILURE;
        }

        try {
            // 1. Precarga de datos existentes
            $this->precargarDatos($io);

            // 2. Procesamiento del archivo
            $this->procesarArchivo($rutaArchivo, $io);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error durante la importación: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function precargarDatos(SymfonyStyle $io): void
    {
        $em = $this->doctrine->getManager();
        
        // Cargar todos los rubros existentes
        $rubros = $em->getRepository(Rubro::class)->findAll();
        foreach ($rubros as $rubro) {
            $this->rubrosCache[$rubro->getCodigo()] = $rubro;
        }
        
        // Cargar todos los subrubros existentes
        $subrubros = $em->getRepository(Subrubro::class)->findAll();
        foreach ($subrubros as $subrubro) {
            $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
        }
        
        // Cargar todas las marcas existentes
        $marcas = $em->getRepository(Marca::class)->findAll();
        foreach ($marcas as $marca) {
            $this->marcasCache[$marca->getCodigo()] = $marca;
        }

        $io->info(sprintf('Precargados %d rubros, %d subrubros y %d marcas', 
            count($this->rubrosCache), 
            count($this->subrubrosCache),
            count($this->marcasCache)
        ));
    }

    private function procesarArchivo(string $rutaArchivo, SymfonyStyle $io): void
    {
        $handle = fopen($rutaArchivo, 'r');
        if ($handle === false) {
            throw new \Exception('No se pudo abrir el archivo');
        }

        $lote = [];
        $contador = 0;
        
        // Saltar encabezados
        fgets($handle);

        while (($linea = fgetcsv($handle, 0, ';')) !== false) {
            $linea = array_map(function($valor) {
                return mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
            }, $linea);

            try {
                $lote[] = $this->crearArticulo($linea);
                $contador++;

                if (count($lote) >= self::LOTE_TAMANIO) {
                    $this->guardarLote($lote, $io);
                    $lote = [];
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Error en línea %d: %s', $contador, $e->getMessage()));
            }
        }

        if (!empty($lote)) {
            $this->guardarLote($lote, $io);
        }

        fclose($handle);
        $io->success(sprintf('Importación completada. Total: %d artículos', $contador));
    }

    private function guardarLote(array $articulos, SymfonyStyle $io): void
    {
        $em = $this->doctrine->getManager();

        try {
            // Obtener códigos únicos de rubros, subrubros y marcas del lote actual
            $rubrosCodigos = [];
            $subrubrosCodigos = [];
            $marcasCodigos = [];
            
            foreach ($articulos as $articulo) {
                $rubrosCodigos[] = $articulo->getSubrubro()->getRubro()->getCodigo();
                $subrubrosCodigos[] = $articulo->getSubrubro()->getCodigo();
                if ($articulo->getMarca()) {
                    $marcasCodigos[] = $articulo->getMarca()->getCodigo();
                }
            }
            
            $rubrosCodigos = array_unique($rubrosCodigos);
            $subrubrosCodigos = array_unique($subrubrosCodigos);
            $marcasCodigos = array_unique($marcasCodigos);

            // Verificar rubros existentes
            $rubrosExistentes = $em->createQueryBuilder()
                ->select('r')
                ->from(Rubro::class, 'r')
                ->where('r.codigo IN (:codigos)')
                ->setParameter('codigos', $rubrosCodigos)
                ->getQuery()
                ->getResult();

            // Actualizar caché de rubros con los existentes
            foreach ($rubrosExistentes as $rubro) {
                $this->rubrosCache[$rubro->getCodigo()] = $rubro;
            }

            // Verificar subrubros existentes
            $subrubrosExistentes = $em->createQueryBuilder()
                ->select('s')
                ->from(Subrubro::class, 's')
                ->where('s.codigo IN (:codigos)')
                ->setParameter('codigos', $subrubrosCodigos)
                ->getQuery()
                ->getResult();

            // Actualizar caché de subrubros con los existentes
            foreach ($subrubrosExistentes as $subrubro) {
                $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
            }
            
            // Verificar marcas existentes
            if (!empty($marcasCodigos)) {
                $marcasExistentes = $em->createQueryBuilder()
                    ->select('m')
                    ->from(Marca::class, 'm')
                    ->where('m.codigo IN (:codigos)')
                    ->setParameter('codigos', $marcasCodigos)
                    ->getQuery()
                    ->getResult();

                // Actualizar caché de marcas con las existentes
                foreach ($marcasExistentes as $marca) {
                    $this->marcasCache[$marca->getCodigo()] = $marca;
                }
            }

            // Persistir solo los rubros, subrubros y marcas nuevos
            foreach ($articulos as $articulo) {
                $rubro = $articulo->getSubrubro()->getRubro();
                $subrubro = $articulo->getSubrubro();
                $marca = $articulo->getMarca();

                // Solo persistir rubros nuevos
                if (!$em->contains($rubro)) {
                    $em->persist($rubro);
                }

                // Solo persistir subrubros nuevos
                if (!$em->contains($subrubro)) {
                    $em->persist($subrubro);
                }
                
                // Solo persistir marcas nuevas
                if ($marca && !$em->contains($marca)) {
                    $em->persist($marca);
                }

                $em->persist($articulo);
            }

            $em->flush();
            
            // Limpiar el EntityManager y recargar las entidades del caché
            $em->clear();
            
            // Recargar las entidades en el caché después del clear
            $this->recargarCache($em);
            
            gc_collect_cycles();

            $io->info(sprintf('Procesados %d artículos', count($articulos)));
        } catch (\Exception $e) {
            if ($em->isOpen()) {
                $em->close();
            }
            $this->doctrine->resetManager();
            throw $e;
        }
    }

    private function recargarCache(EntityManager $em): void
    {
        // Recargar rubros
        $rubros = $em->createQueryBuilder()
            ->select('r')
            ->from(Rubro::class, 'r')
            ->where('r.codigo IN (:codigos)')
            ->setParameter('codigos', array_keys($this->rubrosCache))
            ->getQuery()
            ->getResult();

        $this->rubrosCache = [];
        foreach ($rubros as $rubro) {
            $this->rubrosCache[$rubro->getCodigo()] = $rubro;
        }

        // Recargar subrubros
        $subrubros = $em->createQueryBuilder()
            ->select('s')
            ->from(Subrubro::class, 's')
            ->where('s.codigo IN (:codigos)')
            ->setParameter('codigos', array_keys($this->subrubrosCache))
            ->getQuery()
            ->getResult();

        $this->subrubrosCache = [];
        foreach ($subrubros as $subrubro) {
            $this->subrubrosCache[$subrubro->getCodigo()] = $subrubro;
        }
        
        // Recargar marcas
        if (!empty($this->marcasCache)) {
            $marcas = $em->createQueryBuilder()
                ->select('m')
                ->from(Marca::class, 'm')
                ->where('m.codigo IN (:codigos)')
                ->setParameter('codigos', array_keys($this->marcasCache))
                ->getQuery()
                ->getResult();

            $this->marcasCache = [];
            foreach ($marcas as $marca) {
                $this->marcasCache[$marca->getCodigo()] = $marca;
            }
        }
    }

    private function crearArticulo(array $linea): Articulo
    {
        $rubroCodigo = $linea[2];
        $subrubroCodigo = $linea[4];
        $marcaCodigo = $linea[6]; // Usar el código de marca directamente del archivo
        $marcaNombre = $linea[7]; // Asumo que en $linea[6] está el nombre de la marca

        // Obtener o crear rubro
        if (!isset($this->rubrosCache[$rubroCodigo])) {
            $rubro = new Rubro();
            $rubro->setCodigo($rubroCodigo);
            $rubro->setNombre($linea[3]);
            $this->rubrosCache[$rubroCodigo] = $rubro;
        }

        // Obtener o crear subrubro
        if (!isset($this->subrubrosCache[$subrubroCodigo])) {
            $subrubro = new Subrubro();
            $subrubro->setCodigo($subrubroCodigo);
            $subrubro->setNombre($linea[5]);
            $subrubro->setRubro($this->rubrosCache[$rubroCodigo]);
            $this->subrubrosCache[$subrubroCodigo] = $subrubro;
        }
        
        // Obtener o crear marca si existe el código
        $marca = null;
        if (!empty($marcaCodigo)) {
            if (!isset($this->marcasCache[$marcaCodigo])) {
                $marca = new Marca();
                $marca->setCodigo($marcaCodigo);
                $marca->setNombre($marcaNombre ?: $marcaCodigo); // Usar el nombre o el código si no hay nombre
                $marca->setHabilitado(true);
                $this->marcasCache[$marcaCodigo] = $marca;
            } else {
                $marca = $this->marcasCache[$marcaCodigo];
            }
        }

        $articulo = new Articulo();
        $articulo->setCodigo($linea[0]);
        $articulo->setDetalle($linea[1]);
        $articulo->setMarca($marca); // Ahora asignamos el objeto Marca
        $articulo->setModelo($linea[12]);
        $articulo->setDetalleWeb($linea[13]);
        $articulo->setImpuesto($linea[8]);
        $articulo->setPrecioLista($linea[9]);
        $articulo->setPrecio400($linea[9]);
        $articulo->setDestacado(false);
        $articulo->setHabilitadoWeb(true);
        $articulo->setHabilitadoGestion($linea[5]);
        $articulo->setNovedad(false);
        $articulo->setSubrubro($this->subrubrosCache[$subrubroCodigo]);

        return $articulo;
    }
}