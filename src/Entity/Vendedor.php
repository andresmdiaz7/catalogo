<?php

namespace App\Entity;

use App\Repository\VendedorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VendedorRepository::class)]
class Vendedor 
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $apellido = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $telefono = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\Email]
    #[Assert\Length(max: 100)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $activo = true;

    #[ORM\OneToMany(mappedBy: 'vendedor', targetEntity: Cliente::class)]
    private Collection $clientes;

    #[ORM\ManyToOne(targetEntity: TipoUsuario::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?TipoUsuario $tipoUsuario = null;

    public function __construct()
    {
        $this->clientes = new ArrayCollection();
    }

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

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): static
    {
        $this->apellido = $apellido;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * @return Collection<int, Cliente>
     */
    public function getClientes(): Collection
    {
        return $this->clientes;
    }

    public function addCliente(Cliente $cliente): static
    {
        if (!$this->clientes->contains($cliente)) {
            $this->clientes->add($cliente);
            $cliente->setVendedor($this);
        }

        return $this;
    }

    public function removeCliente(Cliente $cliente): static
    {
        if ($this->clientes->removeElement($cliente)) {
            // set the owning side to null (unless already changed)
            if ($cliente->getVendedor() === $this) {
                $cliente->setVendedor(null);
            }
        }

        return $this;
    }

    public function getNombreCompleto(): string
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    public function __toString(): string
    {
        return $this->getNombreCompleto();
    }

    public function getTipoUsuario(): ?TipoUsuario
    {
        return $this->tipoUsuario;
    }

    public function setTipoUsuario(?TipoUsuario $tipoUsuario): static
    {
        $this->tipoUsuario = $tipoUsuario;
        return $this;
    }
}