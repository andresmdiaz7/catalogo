<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $porDefecto = false;

    #[ORM\Column]
    private ?bool $activo = true;

    #[ORM\OneToMany(targetEntity: MenuSeccion::class, mappedBy: 'menu')]
    private Collection $menuSecciones;

    public function __construct()
    {
        $this->menuSecciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPorDefecto(): ?bool
    {
        return $this->porDefecto;
    }

    public function setPorDefecto(bool $porDefecto): self
    {
        $this->porDefecto = $porDefecto;
        return $this;
    }

    public function getActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    public function getMenuSecciones(): Collection
    {
        return $this->menuSecciones;
    }

    public function addMenuSeccion(MenuSeccion $menuSeccion): self
    {
        if (!$this->menuSecciones->contains($menuSeccion)) {
            $this->menuSecciones[] = $menuSeccion;
            $menuSeccion->setMenu($this);
        }
        return $this;
    }

    public function removeMenuSeccion(MenuSeccion $menuSeccion): self
    {
        if ($this->menuSecciones->removeElement($menuSeccion)) {
            if ($menuSeccion->getMenu() === $this) {
                $menuSeccion->setMenu(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}