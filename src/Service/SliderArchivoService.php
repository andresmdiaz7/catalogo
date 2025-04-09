<?php
namespace App\Service;

use App\Repository\SliderArchivoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class SliderArchivoService
{
    private $sliderArchivoRepository;
    private $entityManager;
    private $sliderDirectory;
    private $filesystem;

    public function __construct(
        SliderArchivoRepository $sliderArchivoRepository,
        EntityManagerInterface $entityManager,
        string $sliderDirectory,
        Filesystem $filesystem
    ) {
        $this->sliderArchivoRepository = $sliderArchivoRepository;
        $this->entityManager = $entityManager;
        $this->sliderDirectory = $sliderDirectory;
        $this->filesystem = $filesystem;
    }

    public function limpiarArchivosNoUtilizados(): int
    {
        // Obtener todos los archivos en el directorio de sliders
        $finder = new Finder();
        $finder->files()->in($this->sliderDirectory);
        
        // Obtener todos los archivos referenciados en la base de datos
        $archivosDB = $this->sliderArchivoRepository->findAll();
        $archivosReferenciados = [];
        
        foreach ($archivosDB as $archivo) {
            $archivosReferenciados[] = $archivo->getFilePath();
            if ($archivo->getFilePathMobile()) {
                $archivosReferenciados[] = $archivo->getFilePathMobile();
            }
        }
        
        // Contar archivos eliminados
        $archivosEliminados = 0;
        
        // Eliminar archivos que no están referenciados
        foreach ($finder as $file) {
            $nombreArchivo = $file->getFilename();
            if (!in_array($nombreArchivo, $archivosReferenciados)) {
                $this->filesystem->remove($file->getRealPath());
                $archivosEliminados++;
            }
        }
        
        return $archivosEliminados;
    }
}
