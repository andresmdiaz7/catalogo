<?php

namespace App\Entity;

use App\Repository\MarcaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarcaRepository::class)]
class Marca
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $codigo = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitado = true;

    #[ORM\OneToMany(mappedBy: 'marca', targetEntity: Articulo::class)]
    private Collection $articulos;

    public function __construct()
    {
        $this->articulos = new ArrayCollection();
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function isHabilitado(): bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): self
    {
        $this->habilitado = $habilitado;
        return $this;
    }

    /**
     * @return Collection<int, Articulo>
     */
    public function getArticulos(): Collection
    {
        return $this->articulos;
    }

    public function addArticulo(Articulo $articulo): self
    {
        if (!$this->articulos->contains($articulo)) {
            $this->articulos->add($articulo);
            $articulo->setMarca($this);
        }
        return $this;
    }

    public function removeArticulo(Articulo $articulo): self
    {
        if ($this->articulos->removeElement($articulo)) {
            // Establece la marca como null si este artículo tenía esta marca
            if ($articulo->getMarca() === $this) {
                $articulo->setMarca(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}