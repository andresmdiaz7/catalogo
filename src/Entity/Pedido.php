<?php

namespace App\Entity;
use App\Repository\PedidoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\EstadoPedido;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedido')]
class Pedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cliente $cliente = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha;

    #[ORM\Column(type: 'string', enumType: EstadoPedido::class)]
    private EstadoPedido $estado = EstadoPedido::PENDIENTE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaLeido = null; // Renombrado de fechaPedido a fechaLeido

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observaciones = null;

    #[ORM\OneToMany(
        mappedBy: 'pedido', 
        targetEntity: PedidoDetalle::class, 
        orphanRemoval: true, 
        cascade: ['persist']
    )]
    private Collection $detalles;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $descuento = null; // Nuevo campo descuento

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $listaPrecio = null; // Nuevo campo listaPrecio

    public function __construct()
    {
        $this->detalles = new ArrayCollection();
        $this->fecha = new \DateTime();
        $this->fechaLeido = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): self
    {
        $this->cliente = $cliente;
        return $this;
    }

    public function getFecha(): \DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self
    {
        $this->fecha = $fecha;
        return $this;
    }

    public function getEstado(): EstadoPedido
    {
        return $this->estado;
    }

    public function setEstado(EstadoPedido $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function getFechaLeido(): ?\DateTimeInterface
    {
        return $this->fechaLeido;
    }

    public function setFechaLeido(?\DateTimeInterface $fechaLeido): static
    {
        $this->fechaLeido = $fechaLeido;
        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): static
    {
        $this->observaciones = $observaciones;
        return $this;
    }

    /**
     * @return Collection<int, PedidoDetalle>
     */
    public function getDetalles(): Collection
    {
        return $this->detalles;
    }

    public function addDetalle(PedidoDetalle $detalle): self
    {
        if (!$this->detalles->contains($detalle)) {
            $this->detalles->add($detalle);
            $detalle->setPedido($this);
        }

        return $this;
    }

    public function removeDetalle(PedidoDetalle $detalle): self
    {
        if ($this->detalles->removeElement($detalle)) {
            // set the owning side to null (unless already changed)
            if ($detalle->getPedido() === $this) {
                $detalle->setPedido(null);
            }
        }

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getDescuento(): ?string
    {
        return $this->descuento;
    }

    public function setDescuento(?string $descuento): static
    {
        $this->descuento = $descuento;
        return $this;
    }

    public function getListaPrecio(): ?string
    {
        return $this->listaPrecio;
    }

    public function setListaPrecio(?string $listaPrecio): static
    {
        $this->listaPrecio = $listaPrecio;
        return $this;
    }

    public function recalcularTotal(): void
    {
        $total = 0;
        foreach ($this->detalles as $detalle) {
            $total += $detalle->getSubtotal();
        }

        // Aplicar descuento si existe
        if ($this->descuento) {
            $descuento = $total * (floatval($this->descuento) / 100);
            $total -= $descuento;
        }

        $this->total = (string) $total;
    }
}

