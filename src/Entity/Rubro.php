<?php

namespace App\Entity;

use App\Repository\RubroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RubroRepository::class)]
class Rubro
{
    #[ORM\Id]
    #[ORM\Column(length: 10, unique: true)]
    private ?string $codigo = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $nombre = null;

    #[ORM\OneToMany(mappedBy: 'rubro', targetEntity: Subrubro::class, orphanRemoval: true, cascade: ['all'])]
    #[ORM\JoinColumn(name: 'codigo', referencedColumnName: 'codigo')]

    private Collection $subrubros;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitado = true;

    #[ORM\ManyToOne(targetEntity: Seccion::class, inversedBy: 'rubros')]
    #[ORM\JoinColumn(name: 'seccion_id', referencedColumnName: 'id', nullable: true)]
    private ?Seccion $seccion = null;

    public function __construct()
    {
        $this->subrubros = new ArrayCollection();
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

    /**
     * @return Collection<int, Subrubro>
     */
    public function getSubrubros(): Collection
    {
        return $this->subrubros;
    }

    public function addSubrubro(Subrubro $subrubro): static
    {
        if (!$this->subrubros->contains($subrubro)) {
            $this->subrubros->add($subrubro);
            $subrubro->setRubro($this);
        }

        return $this;
    }

    public function removeSubrubro(Subrubro $subrubro): static
    {
        if ($this->subrubros->removeElement($subrubro)) {
            // set the owning side to null (unless already changed)
            if ($subrubro->getRubro() === $this) {
                $subrubro->setRubro(null);
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

    public function getSeccion(): ?Seccion
    {
        return $this->seccion;
    }

    public function setSeccion(?Seccion $seccion): static
    {
        $this->seccion = $seccion;
        return $this;
    }
} 