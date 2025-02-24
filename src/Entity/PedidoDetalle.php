<?php

namespace App\Entity;

use App\Repository\PedidoDetalleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PedidoDetalleRepository::class)]
class PedidoDetalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detalles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pedido $pedido = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'codigo', referencedColumnName: 'codigo')]
    private ?Articulo $articulo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $cantidad = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $precioUnitario = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $porcentajeDescuento = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPedido(): ?Pedido
    {
        return $this->pedido;
    }

    public function setPedido(?Pedido $pedido): static
    {
        $this->pedido = $pedido;
        return $this;
    }

    public function getArticulo(): ?Articulo
    {
        return $this->articulo;
    }

    public function setArticulo(?Articulo $articulo): static
    {
        $this->articulo = $articulo;
        if ($articulo) {
            $this->setPrecioUnitario($articulo->getPrecioLista());
        }
        return $this;

    }

    public function getCantidad(): ?string
    {
        return $this->cantidad;
    }

    public function setCantidad(string $cantidad): static
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    public function getPrecioUnitario(): ?string
    {
        return $this->precioUnitario;
    }

    public function setPrecioUnitario(string $precioUnitario): static
    {
        $this->precioUnitario = $precioUnitario;
        return $this;
    }

    public function getPorcentajeDescuento(): ?string
    {
        return $this->porcentajeDescuento;
    }

    public function setPorcentajeDescuento(string $porcentajeDescuento): static
    {
        $this->porcentajeDescuento = $porcentajeDescuento;
        return $this;
    }

    public function getSubtotal(): float
    {
        if (!$this->cantidad || !$this->precioUnitario) {
            return 0;
        }

        $subtotal = floatval($this->cantidad) * floatval($this->precioUnitario);
        if ($this->porcentajeDescuento) {
            $descuento = $subtotal * (floatval($this->porcentajeDescuento) / 100);
            $subtotal -= $descuento;
        }

        return $subtotal;
    }

    public function __toString(): string
    {
        return $this->articulo ? $this->articulo->getDetalle() : '';
    }
} 