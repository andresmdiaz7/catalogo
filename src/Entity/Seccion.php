<?php

namespace App\Entity;

use App\Repository\SeccionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeccionRepository::class)]
class Seccion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\OneToMany(mappedBy: 'seccion', targetEntity: Rubro::class)]
    private Collection $rubros;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitado = true;

    #[ORM\Column(type: 'integer', unique: true)]
    private int $orden = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icono = null;

    public function __construct()
    {
        $this->rubros = new ArrayCollection();
    }

    // Getters y setters

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, Rubro>
     */
    public function getRubros(): Collection
    {
        return $this->rubros;
    }

    public function addRubro(Rubro $rubro): static
    {
        if (!$this->rubros->contains($rubro)) {
            $this->rubros->add($rubro);
            $rubro->setSeccion($this);
        }

        return $this;
    }

    public function removeRubro(Rubro $rubro): static
    {
        if ($this->rubros->removeElement($rubro)) {
            // set the owning side to null (unless already changed)
            if ($rubro->getSeccion() === $this) {
                $rubro->setSeccion(null);
            }
        }

        return $this;
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

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;
        return $this;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): static
    {
        $this->icono = $icono;
        return $this;
    }
} 