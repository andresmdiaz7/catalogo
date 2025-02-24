<?php

namespace App\Entity;

use App\Repository\SubrubroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubrubroRepository::class)]
class Subrubro
{
    #[ORM\Id]
    #[ORM\Column(length: 10, unique: true)]
    private ?string $codigo = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $nombre = null;

    #[ORM\ManyToOne(targetEntity: Rubro::class, cascade: ['all'])]
    #[ORM\JoinColumn(name: "rubro_codigo", referencedColumnName: "codigo", nullable: false)]
    private ?Rubro $rubro = null;

    #[ORM\OneToMany(mappedBy: 'subrubro', targetEntity: Articulo::class)]
    #[ORM\JoinColumn(name: "codigo", referencedColumnName: "codigo")]
    private Collection $articulos;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitado = true;

    public function __construct()
    {
        $this->articulos = new ArrayCollection();
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getRubro(): ?Rubro
    {
        return $this->rubro;
    }

    public function setRubro(?Rubro $rubro): static
    {
        $this->rubro = $rubro;
        return $this;
    }

    /**
     * @return Collection<int, Articulo>
     */
    public function getArticulos(): Collection
    {
        return $this->articulos;
    }

    public function addArticulo(Articulo $articulo): static
    {
        if (!$this->articulos->contains($articulo)) {
            $this->articulos->add($articulo);
            $articulo->setSubrubro($this);
        }

        return $this;
    }

    public function removeArticulo(Articulo $articulo): static
    {
        if ($this->articulos->removeElement($articulo)) {
            // set the owning side to null (unless already changed)
            if ($articulo->getSubrubro() === $this) {
                $articulo->setSubrubro(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre;
    }

    public function isHabilitado(): bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): static
    {
        $this->habilitado = $habilitado;
        return $this;
    }
} 