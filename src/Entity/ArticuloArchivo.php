<?php

namespace App\Entity;

use App\Repository\ArticuloArchivoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticuloArchivoRepository::class)]
class ArticuloArchivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'archivos')]
    #[ORM\JoinColumn(name: 'codigo', referencedColumnName: 'codigo')]
    private ?Articulo $articulo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombreArchivo = null;

    #[ORM\Column(length: 255)]
    private ?string $rutaArchivo = null;

    #[ORM\Column(length: 100)]
    private ?string $tipoArchivo = null;

    #[ORM\Column]
    private ?bool $esPrincipal = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticulo(): ?Articulo
    {
        return $this->articulo;
    }

    public function setArticulo(?Articulo $articulo): static
    {
        $this->articulo = $articulo;
        return $this;
    }

    public function getNombreArchivo(): ?string
    {
        return $this->nombreArchivo;
    }

    public function setNombreArchivo(string $nombreArchivo): static
    {
        $this->nombreArchivo = $nombreArchivo;
        return $this;
    }

    public function getRutaArchivo(): ?string
    {
        return $this->rutaArchivo;
    }

    public function setRutaArchivo(string $rutaArchivo): static
    {
        $this->rutaArchivo = $rutaArchivo;
        return $this;
    }

    public function getTipoArchivo(): ?string
    {
        return $this->tipoArchivo;
    }

    public function setTipoArchivo(string $tipoArchivo): static
    {
        $this->tipoArchivo = $tipoArchivo;
        return $this;
    }

    public function isEsPrincipal(): ?bool
    {
        return $this->esPrincipal;
    }

    public function setEsPrincipal(bool $esPrincipal): static
    {
        $this->esPrincipal = $esPrincipal;
        return $this;
    }
} 