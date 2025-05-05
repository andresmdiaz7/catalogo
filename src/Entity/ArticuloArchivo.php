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
    #[ORM\JoinColumn(name: 'articulo_codigo', referencedColumnName: 'codigo', nullable: false)]
    private ?Articulo $articulo = null;

    #[ORM\ManyToOne(inversedBy: 'articuloArchivos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Archivo $archivo = null;

    #[ORM\Column]
    private ?bool $esPrincipal = false;
    
    #[ORM\Column(nullable: true)]
    private ?int $posicion = null;

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

    public function getArchivo(): ?Archivo
    {
        return $this->archivo;
    }

    public function setArchivo(?Archivo $archivo): static
    {
        $this->archivo = $archivo;
        return $this;
    }

    public function IsEsPrincipal(): ?bool
    {
        return $this->esPrincipal;
    }

    public function setEsPrincipal(bool $esPrincipal): static
    {
        $this->esPrincipal = $esPrincipal;
        return $this;
    }
    
    public function getPosicion(): ?int
    {
        return $this->posicion;
    }
    
    public function setPosicion(?int $posicion): static
    {
        $this->posicion = $posicion;
        return $this;
    }
}