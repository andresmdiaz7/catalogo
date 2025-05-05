<?php

namespace App\Asistente\DTO;

use App\Entity\Articulo;
use App\Entity\Archivo;

/**
 * DTO para estructurar la respuesta de un producto en el chat.
 */
class RespuestaProductoDTO
{
    public string $codigo;
    public string $detalle;
    public string $marca;
    public float $precio;
    public ?string $imagen = null;
    public ?string $fichaTecnica = null;

    public static function fromEntity(Articulo $articulo): self
    {
        $dto = new self();
        $dto->codigo = $articulo->getCodigo();
        $dto->detalle = $articulo->getDetalle();
        $dto->marca = $articulo->getMarca() ? $articulo->getMarca()->getNombre() : '';
        $dto->precio = $articulo->getPrecioLista();

        // Imagen principal
        $imagen = $articulo->getImagenPrincipal();
        if ($imagen) {
            $dto->imagen = $imagen->getUrlArchivo();
        }

        // Buscar ficha técnica (primer archivo PDF asociado)
        foreach ($articulo->getArchivos() as $articuloArchivo) {
            $archivo = $articuloArchivo->getArchivo();
            if (str_starts_with($archivo->getTipoMime(), 'application/pdf')) {
                $dto->fichaTecnica = $archivo->getUrlArchivo();
                break;
            }
        }

        return $dto;
    }
} 