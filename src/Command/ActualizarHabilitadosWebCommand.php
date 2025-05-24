<?php

namespace App\Command;

use App\Entity\Articulo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:actualizar-habilitados-web',
    description: 'Actualiza el campo habilitadoWeb de artículos desde un archivo CSV',
)]
class ActualizarHabilitadosWebCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvFile = 'imports/HabilitadosWeb.csv';
        
        // Verificar que existe el archivo
        if (!file_exists($csvFile)) {
            $io->error('El archivo imports/HabilitadosWeb.csv no existe');
            return Command::FAILURE;
        }

        // Variables para estadísticas
        $procesados = 0;
        $actualizados = 0;
        $noEncontrados = 0;
        $errores = [];
        
        // Desactivar el logger de Doctrine para mejorar el rendimiento
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        
        $io->title('Actualizando estado habilitadoWeb de artículos');
        
        // Contar número total de líneas para la barra de progreso
        $totalLineas = count(file($csvFile));
        $io->progressStart($totalLineas);
        
        // Abrir archivo CSV
        $file = fopen($csvFile, 'r');
        
        // Tamaño del lote para flush
        $tamanoLote = 500;
        
        while (($data = fgetcsv($file, 0, ';', '"')) !== false) {
            $io->progressAdvance();
            
            // Verificar estructura de datos
            if (count($data) !== 2) {
                $errores[] = "Línea inválida: " . implode(';', $data);
                continue;
            }
            
            $codigoArticulo = trim($data[0]);
            $habilitadoWeb = strtolower(trim($data[1]));
            
            // Convertir valor a booleano
            if ($habilitadoWeb === '1' || $habilitadoWeb === 'true' || $habilitadoWeb === 'si' || $habilitadoWeb === 'yes') {
                $valorHabilitado = true;
            } else {
                $valorHabilitado = false;
            }
            
            try {
                // Buscar el artículo
                $articulo = $this->entityManager->getRepository(Articulo::class)
                    ->findOneBy(['codigo' => $codigoArticulo]);
                
                if (!$articulo) {
                    $noEncontrados++;
                    $errores[] = "Artículo no encontrado: {$codigoArticulo}";
                    continue;
                }
                
                // Actualizar solo si el valor es diferente
                if ($articulo->isHabilitadoWeb() !== $valorHabilitado) {
                    $articulo->setHabilitadoWeb($valorHabilitado);
                    $actualizados++;
                }
                
                $procesados++;
                
                // Hacer flush cada cierto número de registros
                if ($procesados % $tamanoLote === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } catch (\Exception $e) {
                $errores[] = "Error procesando artículo {$codigoArticulo}: " . $e->getMessage();
                $this->logger->error("Error procesando artículo {$codigoArticulo}: " . $e->getMessage());
            }
        }
        
        fclose($file);
        
        // Guardar los cambios pendientes
        $this->entityManager->flush();
        $io->progressFinish();
        
        if (!empty($errores)) {
            $io->warning('Se encontraron los siguientes errores:');
            foreach (array_slice($errores, 0, 10) as $error) {
                $io->text($error);
            }
            
            if (count($errores) > 10) {
                $io->text('... y ' . (count($errores) - 10) . ' errores más.');
            }
            
            // Guardar errores en un archivo log
            file_put_contents('var/log/actualizar_habilitados_web_errores.log', implode("\n", $errores));
            $io->note('Lista completa de errores guardada en var/log/actualizar_habilitados_web_errores.log');
        }
        
        $io->success([
            "Actualización completada.",
            "Registros procesados: {$procesados}",
            "Artículos actualizados: {$actualizados}",
            "Artículos no encontrados: {$noEncontrados}",
            "Errores encontrados: " . count($errores)
        ]);
        
        return Command::SUCCESS;
    }
} 