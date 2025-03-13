<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
class Cliente implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: Pedido::class)]
    private Collection $pedidos;

    #[ORM\Column(length: 50)]
    private ?string $codigo = null;

    #[ORM\Column(length: 255)]
    private ?string $razonSocial = null;

    #[ORM\ManyToOne(targetEntity: CategoriaImpositiva::class, inversedBy: "clientes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoriaImpositiva $categoriaImpositiva = null;

    #[ORM\Column(length: 20)]
    private ?string $cuit = null;

    #[ORM\ManyToOne(targetEntity: TipoCliente::class, inversedBy: "clientes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?TipoCliente $tipoCliente = null;

    #[ORM\Column(length: 255)]
    private ?string $direccion = null;

    #[ORM\ManyToOne(targetEntity: Localidad::class, inversedBy: "clientes")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Localidad $localidad = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $porcentajeDescuento = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $rentabilidad = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $ultimaVisita = null;

    #[ORM\ManyToOne(targetEntity: Vendedor::class, inversedBy: "clientes")]
    #[ORM\JoinColumn(nullable: true)]  // Cambiado a true para permitir valores nulos
    private ?Vendedor $vendedor = null;

    #[ORM\ManyToOne(targetEntity: ResponsableLogistica::class, inversedBy: "clientes")]
    #[ORM\JoinColumn(nullable: true)]  // Cambiado a true para permitir valores nulos
    private ?ResponsableLogistica $responsableLogistica = null;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: 'clientes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categoria $categoria = null;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitado = true;

    public function __construct()
    {
        $this->pedidos = new ArrayCollection();
    }

    // Implementación de UserInterface
    public function getRoles(): array
    {
        return ['ROLE_CLIENTE'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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

    public function getRazonSocial(): ?string
    {
        return $this->razonSocial;
    }

    public function setRazonSocial(string $razonSocial): self
    {
        $this->razonSocial = $razonSocial;
        return $this;
    }

    public function getCategoriaImpositiva(): ?CategoriaImpositiva
    {
        return $this->categoriaImpositiva;
    }

    public function setCategoriaImpositiva(?CategoriaImpositiva $categoriaImpositiva): static
    {
        $this->categoriaImpositiva = $categoriaImpositiva;
        return $this;
    }

    public function getCuit(): ?string
    {
        return $this->cuit;
    }

    public function setCuit(string $cuit): self
    {
        $this->cuit = $cuit;
        return $this;
    }

    public function getTipoCliente(): ?TipoCliente
    {
        return $this->tipoCliente;
    }

    public function setTipoCliente(?TipoCliente $tipoCliente): self
    {
        $this->tipoCliente = $tipoCliente;
        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): self
    {
        $this->direccion = $direccion;
        return $this;
    }

    public function getLocalidad(): ?Localidad
    {
        return $this->localidad;
    }

    public function setLocalidad(?Localidad $localidad): self
    {
        $this->localidad = $localidad;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
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

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(?string $web): self
    {
        $this->web = $web;
        return $this;
    }

    public function getPorcentajeDescuento(): ?float
    {
        return $this->porcentajeDescuento;
    }

    public function setPorcentajeDescuento(float $porcentajeDescuento): self
    {
        $this->porcentajeDescuento = $porcentajeDescuento;
        return $this;
    }

    public function getRentabilidad(): ?float
    {
        return $this->rentabilidad;
    }

    public function setRentabilidad(float $rentabilidad): self
    {
        $this->rentabilidad = $rentabilidad;
        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): self
    {
        $this->observaciones = $observaciones;
        return $this;
    }

    public function getUltimaVisita(): ?\DateTimeInterface
    {
        return $this->ultimaVisita;
    }

    public function setUltimaVisita(?\DateTimeInterface $ultimaVisita): self
    {
        $this->ultimaVisita = $ultimaVisita;
        return $this;
    }

    public function getVendedor(): ?Vendedor
    {
        return $this->vendedor;
    }

    public function setVendedor(?Vendedor $vendedor): self
    {
        $this->vendedor = $vendedor;
        return $this;
    }

    public function getResponsableLogistica(): ?ResponsableLogistica
    {
        return $this->responsableLogistica;
    }

    public function setResponsableLogistica(?ResponsableLogistica $responsableLogistica): self
    {
        $this->responsableLogistica = $responsableLogistica;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): self
    {
        $this->categoria = $categoria;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Pedido>
     */
    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedido $pedido): static
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos->add($pedido);
            $pedido->setCliente($this);
        }

        return $this;
    }

    public function removePedido(Pedido $pedido): static
    {
        if ($this->pedidos->removeElement($pedido)) {
            // set the owning side to null (unless already changed)
            if ($pedido->getCliente() === $this) {
                $pedido->setCliente(null);
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
}