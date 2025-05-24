<?php

namespace App\Command;

use App\Entity\Archivo;
use App\Entity\Articulo;
use App\Entity\ArticuloArchivo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\MimeTypes;

#[AsCommand(
    name: 'app:importar-imagenes',
    description: 'Importa imágenes a artículos usando el nuevo modelo de archivos compartidos',
)]
class ImportarImagenesCommand extends Command
{
    private $mimeTypes;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->mimeTypes = new MimeTypes();
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
        $errores = [];
        $archivosCreados = 0;
        $archivosReutilizados = 0;
        $articulosActualizados = 0;
        $rutasVacias = 0;

        // Desactivamos temporalmente el logger de Doctrine para mejorar el rendimiento
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        
        $io->title('Importando imágenes a artículos');
        $io->progressStart(count(file($csvFile)));

        while (($data = fgetcsv($file, 0, ';', '"')) !== false) {
            $io->progressAdvance();
            
            // Verificar estructura de datos
            if (count($data) !== 2) {
                $errores[] = "Línea inválida: " . implode(';', $data);
                continue;
            }

            $codigoArticulo = trim($data[0]);
            $rutaArchivo = trim($data[1]);
            
            // Verificar ruta vacía
            if (empty($rutaArchivo)) {
                $errores[] = "Ruta de archivo vacía para artículo {$codigoArticulo}";
                $rutasVacias++;
                continue;
            }
            
            // Verificar que existe el archivo físico
            $rutaCompleta = 'public/uploads/archivos/' . $rutaArchivo;
            if (!file_exists($rutaCompleta) || !is_file($rutaCompleta)) {
                $errores[] = "Archivo no encontrado: {$rutaCompleta} para artículo {$codigoArticulo}";
                continue;
            }

            // Buscar el artículo
            $articulo = $this->entityManager->getRepository(Articulo::class)
                ->findOneBy(['codigo' => $codigoArticulo]);

            if (!$articulo) {
                $errores[] = "Artículo no encontrado: {$codigoArticulo}";
                continue;
            }
            
            try {
                // Calcular el hash del archivo para identificar duplicados
                $fileContents = @file_get_contents($rutaCompleta);
                
                if ($fileContents === false) {
                    $errores[] = "No se pudo leer el archivo: {$rutaCompleta}";
                    continue;
                }
                
                $hash = md5($fileContents);
                
                // Verificar si ya existe un archivo con este hash
                $archivoExistente = $this->entityManager->getRepository(Archivo::class)
                    ->findOneBy(['hash' => $hash]);
                    
                if (!$archivoExistente) {
                    // Crear nuevo registro de archivo
                    $extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
                    $mimeType = $this->mimeTypes->getMimeTypes($extension)[0] ?? 'application/octet-stream';
                    
                    $archivo = new Archivo();
                    $archivo->setFileName(basename($rutaArchivo));
                    $archivo->setFilePath($rutaArchivo);
                    $archivo->setTipoMime($mimeType);
                    $archivo->setTamanio(filesize($rutaCompleta) );;  
                    $archivo->setHash($hash);
                    
                    $this->entityManager->persist($archivo);
                    $archivosCreados++;
                } else {
                    //$extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
                    //$mimeType = $this->mimeTypes->getMimeTypes($extension)[0] ?? 'application/octet-stream';
                    $archivo = $archivoExistente;
                    //$archivo->setFileName(basename($rutaArchivo));
                    //$archivo->setFilePath($rutaArchivo);
                    //$archivo->setTamanio(filesize($rutaCompleta) );;  
                    
                    $archivosReutilizados++;
                }
                
                // Verificar si el artículo ya tiene este archivo asignado
                $existeRelacion = $this->entityManager->getRepository(ArticuloArchivo::class)
                    ->findOneBy([
                        'articulo' => $articulo,
                        'archivo' => $archivo
                    ]);
                    
                if (!$existeRelacion) {
                    // Crear la relación entre artículo y archivo
                    $articuloArchivo = new ArticuloArchivo();
                    $articuloArchivo->setArticulo($articulo);
                    $articuloArchivo->setArchivo($archivo);
                    
                    // Si es la primera imagen del artículo, establecerla como principal
                    $esPrincipal = count($articulo->getArchivos()) === 0;
                    $articuloArchivo->setEsPrincipal($esPrincipal);
                    
                    // Posición (número de imágenes + 1)
                    $articuloArchivo->setPosicion(count($articulo->getArchivos()) + 1);
                    
                    $this->entityManager->persist($articuloArchivo);
                    $articulosActualizados++;
                }
            } catch (\Exception $e) {
                $errores[] = "Error procesando {$rutaCompleta}: " . $e->getMessage();
                continue;
            }

            $count++;

            // Flush cada cierto número de registros para liberar memoria
            if ($count % 1000 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Limpiar el gestor de entidades
                
                // Volver a cargar el repositorio después de limpiar
                $articuloRepository = $this->entityManager->getRepository(Articulo::class);
                $archivoRepository = $this->entityManager->getRepository(Archivo::class);
                $articuloArchivoRepository = $this->entityManager->getRepository(ArticuloArchivo::class);
            }
        }

        fclose($file);
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
            file_put_contents('var/log/importar_imagenes_errores.log', implode("\n", $errores));
            $io->note('Lista completa de errores guardada en var/log/importar_imagenes_errores.log');
        }

        $io->success([
            "Importación completada.",
            "Registros procesados: {$count}",
            "Archivos nuevos creados: {$archivosCreados}",
            "Archivos reutilizados: {$archivosReutilizados}",
            "Artículos actualizados: {$articulosActualizados}",
            "Errores encontrados: " . count($errores)
        ]);
        
        return Command::SUCCESS;
    }
}