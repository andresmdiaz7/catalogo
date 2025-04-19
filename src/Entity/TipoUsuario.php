<?php
// src/Entity/TipoUsuario.php

namespace App\Entity;

use App\Repository\TipoUsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TipoUsuarioRepository::class)]
#[ORM\Table(name: 'tipo_usuario')]
class TipoUsuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $codigo = null; // 'admin', 'cliente', 'vendedor', etc.

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $nombre = null; // 'Administrador', 'Cliente', etc.

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\OneToMany(mappedBy: 'tipoUsuario', targetEntity: Usuario::class)]
    private Collection $usuarios;

    #[ORM\Column]
    private bool $activo = true;

    public function __construct()
    {
        $this->usuarios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    /**
     * @return Collection<int, Usuario>
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }

    public function addUsuario(Usuario $usuario): self
    {
        if (!$this->usuarios->contains($usuario)) {
            $this->usuarios->add($usuario);
            $usuario->setTipoUsuario($this);
        }
        return $this;
    }

    public function removeUsuario(Usuario $usuario): self
    {
        if ($this->usuarios->removeElement($usuario)) {
            if ($usuario->getTipoUsuario() === $this) {
                $usuario->setTipoUsuario(null);
            }
        }
        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}
