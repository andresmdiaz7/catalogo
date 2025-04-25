<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'usuario')]
#[UniqueEntity(
    fields: ['email'],
    message: 'Este correo electrónico ya está registrado en el sistema'
)]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TipoUsuario $tipoUsuario = null;

    #[ORM\Column(type: 'boolean')]
    private bool $activo = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $ultimoAcceso = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombreReferencia = null;

    #[ORM\OneToMany(mappedBy: 'usuario', targetEntity: Cliente::class)]
    private Collection $clientes;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->clientes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si almacenas temporalmente algún dato sensible, límpialo aquí
    }

    public function getTipoUsuario(): ?TipoUsuario
    {
        return $this->tipoUsuario;
    }

    public function setTipoUsuario(?TipoUsuario $tipoUsuario): self
    {
        $this->tipoUsuario = $tipoUsuario;
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

    public function getUltimoAcceso(): ?\DateTimeInterface
    {
        return $this->ultimoAcceso;
    }

    public function setUltimoAcceso(?\DateTimeInterface $ultimoAcceso): self
    {
        $this->ultimoAcceso = $ultimoAcceso;
        return $this;
    }

    public function getNombreReferencia(): ?string
    {
        return $this->nombreReferencia;
    }

    public function setNombreReferencia(?string $nombreReferencia): self
    {
        $this->nombreReferencia = $nombreReferencia;
        return $this;
    }

    /**
     * @return Collection<int, Cliente>
     */
    public function getClientes(): Collection
    {
        return $this->clientes;
    }

    public function addCliente(Cliente $cliente): self
    {
        if (!$this->clientes->contains($cliente)) {
            $this->clientes->add($cliente);
            $cliente->setUsuario($this);
        }
        
        return $this;
    }

    public function removeCliente(Cliente $cliente): self
    {
        if ($this->clientes->removeElement($cliente)) {
            if ($cliente->getUsuario() === $this) {
                $cliente->setUsuario(null);
            }
        }
        
        return $this;
    }

    /**
     * Retorna true si el usuario tiene al menos un cliente asociado
     */
    public function hasClientes(): bool
    {
        return !$this->clientes->isEmpty();
    }

    /**
     * Retorna el número de clientes asociados
     */
    public function getClientesCount(): int
    {
        return $this->clientes->count();
    }

    /**
     * Retorna el primer cliente si solo hay uno
     */
    public function getUnicoCliente(): ?Cliente
    {
        return $this->clientes->count() === 1 ? $this->clientes->first() : null;
    }

    /**
     * Retorna true si el usuario tiene un solo cliente
     */
    public function hasUnicoCliente(): bool
    {
        return $this->clientes->count() === 1;
    }

    /**
     * Retorna true si el usuario tiene múltiples clientes
     */
    public function hasMultiplesClientes(): bool
    {
        return $this->clientes->count() > 1;
    }
}