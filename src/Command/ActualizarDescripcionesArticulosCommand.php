<?php

namespace App\Command;

use App\Entity\Articulo;
use App\Repository\ArticuloRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:actualizar-descripciones-articulos',
    description: 'Actualiza las descripciones de los artículos desde un archivo CSV',
)]
class ActualizarDescripcionesArticulosCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ArticuloRepository $articuloRepository;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        ArticuloRepository $articuloRepository,
        string $projectDir
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->articuloRepository = $articuloRepository;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando actualiza las descripciones de los artículos a partir de un archivo CSV.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Actualización de descripciones de artículos');

        $csvFile = $this->projectDir . '/imports/articulos_descripciones.csv';
        
        if (!file_exists($csvFile)) {
            $io->error("El archivo $csvFile no existe.");
            return Command::FAILURE;
        }

        try {
            // Configuramos el lector CSV
            $csv = Reader::createFromPath($csvFile, 'r');
            $csv->setDelimiter(',');  // Separados por comas
            $csv->setEnclosure('"');  // Valores encerrados entre comillas dobles
            $csv->setHeaderOffset(null);  // Sin encabezados
            
            // Mostramos las primeras líneas para confirmar
            $io->section('Primeras 3 líneas del CSV:');
            for ($i = 0; $i < 3; $i++) {
                $line = $csv->fetchOne($i);
                if ($line) {
                    $io->writeln("Línea " . ($i + 1) . ": " . json_encode($line));
                }
            }
            
            $records = $csv->getRecords();
            $totalRegistros = count(iterator_to_array($csv->getRecords()));
            $io->info("Total de registros detectados: $totalRegistros");
            
            // Reiniciamos el iterador
            $records = $csv->getRecords();
            
            $actualizados = 0;
            $noEncontrados = 0;
            $errores = 0;

            $io->progressStart($totalRegistros);

            foreach ($records as $index => $record) {
                try {
                    $codigo = trim($record[0] ?? '');
                    $descripcionTexto = trim($record[1] ?? '');
                    
                    if (empty($codigo)) {
                        $io->warning("Fila #$index: Código vacío");
                        $errores++;
                        $io->progressAdvance();
                        continue;
                    }
                    
                    if (empty($descripcionTexto)) {
                        $io->warning("Fila #$index: Descripción vacía para código $codigo");
                        $errores++;
                        $io->progressAdvance();
                        continue;
                    }

                    // Formatear la descripción como HTML
                    $descripcionHtml = $this->formatearComoHtml($descripcionTexto);
                    
                    // Buscar el artículo por código
                    $articulo = $this->articuloRepository->find($codigo);
                    
                    if (!$articulo) {
                        $noEncontrados++;
                        if ($index < 10) { // Solo mostrar los primeros 10 para no saturar
                            $io->warning("No se encontró artículo con código: $codigo");
                        }
                        $io->progressAdvance();
                        continue;
                    }
                    
                    // Actualizar la descripción
                    $articulo->setDescripcion($descripcionHtml);
                    $this->entityManager->persist($articulo);
                    
                    // Hacemos flush cada 50 registros para optimizar rendimiento
                    if ($actualizados > 0 && $actualizados % 50 === 0) {
                        $this->entityManager->flush();
                        $io->info("Procesados $actualizados artículos");
                        // No limpiar el EM para evitar problemas
                    }
                    
                    $actualizados++;
                    $io->progressAdvance();
                    
                } catch (\Exception $e) {
                    $errores++;
                    if ($index < 10) { // Solo mostrar los primeros 10 errores para no saturar
                        $io->error("Error en fila #$index: " . $e->getMessage());
                    }
                    $io->progressAdvance();
                }
            }
            
            // Flush final para asegurar que se guarden todos los cambios
            if ($actualizados > 0) {
                $this->entityManager->flush();
            }
            
            $io->progressFinish();
            
            $io->success([
                "Proceso completado",
                "Total de registros procesados: $totalRegistros",
                "Artículos actualizados: $actualizados",
                "Artículos no encontrados: $noEncontrados",
                "Errores: $errores"
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error("Error al procesar el archivo CSV: " . $e->getMessage());
            $io->error("Trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
    
    /**
     * Formatea un texto plano como HTML
     */
    private function formatearComoHtml(string $texto): string
    {
        // Eliminar caracteres especiales y espacios innecesarios
        $texto = trim($texto);
        
        // Convertir saltos de línea a <br>
        $html = htmlspecialchars($texto);
        $html = str_replace("\n", "<br>", $html);
        
        // Envolver todo en un párrafo para mantener la estructura
        $html = "<p>" . $html . "</p>";
        
        return $html;
    }
} 