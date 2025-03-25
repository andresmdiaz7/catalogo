<?php

namespace App\Service;

use App\Entity\Archivo;
use App\Entity\Articulo;
use App\Entity\ArticuloArchivo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArchivoService
{
    private $entityManager;
    private $archivoDir;
    
    public function __construct(EntityManagerInterface $entityManager, string $archivoDir)
    {
        $this->entityManager = $entityManager;
        $this->archivoDir = $archivoDir;
    }
    
    /**
     * Guarda un archivo y lo asocia con un artículo
     */
    public function guardarArchivo(UploadedFile $file, Articulo $articulo, bool $esPrincipal = false): ArticuloArchivo
    {
        $fileContents = file_get_contents($file->getPathname());
        $hash = md5($fileContents);
        
        // Buscar si ya existe un archivo con el mismo hash
        $archivoExistente = $this->entityManager->getRepository(Archivo::class)
            ->findOneBy(['hash' => $hash]);
        
        if (!$archivoExistente) {
            // Generar nombre único
            $nuevoNombre = uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Mover archivo al directorio de destino
            $file->move($this->archivoDir, $nuevoNombre);
            
            // Crear nuevo registro de archivo
            $archivo = new Archivo();
            $archivo->setNombreArchivo($file->getClientOriginalName());
            $archivo->setRutaArchivo($nuevoNombre);
            $archivo->setTipoArchivo($file->getMimeType());
            $archivo->setHash($hash);
            
            $this->entityManager->persist($archivo);
        } else {
            $archivo = $archivoExistente;
        }
        
        // Crear la relación entre artículo y archivo
        $articuloArchivo = new ArticuloArchivo();
        $articuloArchivo->setArticulo($articulo);
        $articuloArchivo->setArchivo($archivo);
        $articuloArchivo->setEsPrincipal($esPrincipal);
        
        // Establecer posición (último + 1)
        $posicion = count($articulo->getArchivos()) + 1;
        $articuloArchivo->setPosicion($posicion);
        
        $this->entityManager->persist($articuloArchivo);
        $this->entityManager->flush();
        
        return $articuloArchivo;
    }
}