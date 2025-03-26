<?php
// filepath: c:\wamp64\www\catalogo\src\Service\FileUploader.php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileUploader
{
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private string $projectDir;

    public function __construct(
        string $targetDirectory, 
        SluggerInterface $slugger,
        ParameterBagInterface $parameterBag
    ) {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->projectDir = $parameterBag->get('kernel.project_dir');
        
        // Asegurarse de que el directorio base existe
        if (!is_dir($this->targetDirectory) && !mkdir($this->targetDirectory, 0777, true)) {
            throw new \RuntimeException(sprintf('No se pudo crear el directorio "%s"', $this->targetDirectory));
        }
        
        // Crear los subdirectorios del 0 al 99 si no existen
        for ($i = 0; $i < 100; $i++) {
            $subDir = $this->targetDirectory . '/' . $i;
            if (!is_dir($subDir) && !mkdir($subDir, 0777, true)) {
                throw new \RuntimeException(sprintf('No se pudo crear el subdirectorio "%s"', $subDir));
            }
        }
    }

    /**
     * Sube un archivo y devuelve el nombre del archivo generado
     * El archivo se guarda en un subdirectorio aleatorio (0-99)
     */
    public function upload(UploadedFile $file): string
    {
        // Verificar que el archivo existe y es legible
        if (!file_exists($file->getPathname())) {
            throw new \RuntimeException(sprintf('El archivo temporal "%s" no existe', $file->getPathname()));
        }
        
        if (!is_readable($file->getPathname())) {
            throw new \RuntimeException(sprintf('El archivo temporal "%s" no es legible', $file->getPathname()));
        }
        
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        
        // Seleccionar un subdirectorio aleatorio entre 0 y 99
        $subDir = rand(0, 99);
        $targetPath = $subDir . '/' . $fileName;
        
        try {
            // Asegurarse de que el subdirectorio existe
            $fullSubDir = $this->targetDirectory . '/' . $subDir;
            if (!is_dir($fullSubDir)) {
                if (!mkdir($fullSubDir, 0777, true)) {
                    throw new FileException(sprintf('No se pudo crear el subdirectorio "%s"', $fullSubDir));
                }
            }
            
            // Puedes usar el projectDir así:
            $absolutePath = $this->projectDir . '/public/uploads/archivos/' . $subDir;
            
            // Mover el archivo al subdirectorio seleccionado
            $file->move($absolutePath, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Error al subir el archivo: ' . $e->getMessage());
        }

        // Devolver la ruta relativa del archivo (subdirectorio/nombre_archivo)
        return $targetPath;
    }

    /**
     * Elimina un archivo del directorio de carga
     */
    public function remove(string $filePath): bool
    {
        $fullPath = $this->getTargetDirectory() . '/' . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * Obtiene el directorio de carga de archivos
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
    
    /**
     * Obtiene la ruta completa de un archivo
     */
    public function getFullPath(string $filePath): string
    {
        return $this->targetDirectory . '/' . $filePath;
    }
    
    /**
     * Obtiene la ruta absoluta del proyecto
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }
    
    /**
     * Obtiene la ruta absoluta completa de un archivo subido
     */
    public function getAbsoluteFilePath(string $relativePath): string
    {
        return $this->projectDir . '/public/uploads/archivos/' . $relativePath;
    }
}