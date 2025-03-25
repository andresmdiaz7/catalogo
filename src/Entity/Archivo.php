<?php

namespace App\Entity;

use App\Repository\ArchivoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivoRepository::class)]
class Archivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreArchivo = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $rutaArchivo = null;

    #[ORM\Column(length: 100)]
    private ?string $tipoArchivo = null;
    
    #[ORM\OneToMany(mappedBy: 'archivo', targetEntity: ArticuloArchivo::class, orphanRemoval: true)]
    private Collection $articuloArchivos;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hash = null;

    public function __construct()
    {
        $this->articuloArchivos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
    
    public function getHash(): ?string
    {
        return $this->hash;
    }
    
    public function setHash(?string $hash): static
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return Collection<int, ArticuloArchivo>
     */
    public function getArticuloArchivos(): Collection
    {
        return $this->articuloArchivos;
    }

    public function addArticuloArchivo(ArticuloArchivo $articuloArchivo): static
    {
        if (!$this->articuloArchivos->contains($articuloArchivo)) {
            $this->articuloArchivos->add($articuloArchivo);
            $articuloArchivo->setArchivo($this);
        }

        return $this;
    }

    public function removeArticuloArchivo(ArticuloArchivo $articuloArchivo): static
    {
        if ($this->articuloArchivos->removeElement($articuloArchivo)) {
            // set the owning side to null (unless already changed)
            if ($articuloArchivo->getArchivo() === $this) {
                $articuloArchivo->setArchivo(null);
            }
        }

        return $this;
    }
}