<?php

namespace App\Asistente\DTO;

use App\Entity\Articulo;
use App\Entity\Cliente;

/**
 * DTO para estructurar la respuesta de un producto en el chat.
 */
class RespuestaProductoDTO
{
    public string $codigo;
    public string $detalle;
    public string $detalleWeb;
    public string $marca;
    public float $precioLista;
    public ?float $precioPersonalizado = null;
    public ?string $imagen = null;
    public ?string $fichaTecnica = null;
    public ?string $rubro = null;
    public ?string $subrubro = null;
    public ?string $modelo = null;
    public bool $tieneStock = true; // Asumir stock disponible por defecto
    public ?string $urlProducto = null;

    public static function fromEntity(Articulo $articulo, ?float $precioPersonalizado = null): self
    {
        $dto = new self();
        $dto->codigo = $articulo->getCodigo();
        $dto->detalle = $articulo->getDetalle();
        $dto->detalleWeb = $articulo->getDetalleWeb() ?? $articulo->getDetalle();
        $dto->marca = $articulo->getMarca() ? $articulo->getMarca()->getNombre() : 'Sin marca';
        $dto->precioLista = $articulo->getPrecioLista();
        $dto->precioPersonalizado = $precioPersonalizado;
        $dto->modelo = $articulo->getModelo();

        // Información de categorización
        if ($articulo->getSubrubro()) {
            $dto->subrubro = $articulo->getSubrubro()->getNombre();
            if ($articulo->getSubrubro()->getRubro()) {
                $dto->rubro = $articulo->getSubrubro()->getRubro()->getNombre();
            }
        }

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

        // URL del producto para navegación
        $dto->urlProducto = '/articulo/' . $articulo->getCodigo();

        return $dto;
    }

    /**
     * Obtiene el precio a mostrar (personalizado si existe, sino lista)
     */
    public function getPrecioMostrar(): float
    {
        return $this->precioPersonalizado ?? $this->precioLista;
    }

    /**
     * Verifica si tiene precio personalizado
     */
    public function tienePrecioPersonalizado(): bool
    {
        return $this->precioPersonalizado !== null && $this->precioPersonalizado !== $this->precioLista;
    }

    /**
     * Obtiene información resumida del producto para el chat
     */
    public function getResumenParaChat(): string
    {
        $precio = number_format($this->getPrecioMostrar(), 2, ',', '.');
        $marca = $this->marca !== 'Sin marca' ? " ({$this->marca})" : '';
        
        return "{$this->detalleWeb}{$marca} - \${$precio}";
    }

    /**
     * Convierte a array para respuestas JSON
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'detalle' => $this->detalle,
            'detalleWeb' => $this->detalleWeb,
            'marca' => $this->marca,
            'precioLista' => $this->precioLista,
            'precioPersonalizado' => $this->precioPersonalizado,
            'precioMostrar' => $this->getPrecioMostrar(),
            'imagen' => $this->imagen,
            'fichaTecnica' => $this->fichaTecnica,
            'rubro' => $this->rubro,
            'subrubro' => $this->subrubro,
            'modelo' => $this->modelo,
            'tieneStock' => $this->tieneStock,
            'urlProducto' => $this->urlProducto,
            'resumenChat' => $this->getResumenParaChat(),
        ];
    }
} 