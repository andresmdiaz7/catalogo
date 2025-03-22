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
    #[ORM\JoinColumn(name: 'articulo_codigo', referencedColumnName: 'codigo', nullable: true)]
    private ?Articulo $articulo = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $articuloCodigo = null;
    
    #[ORM\Column(length: 255)]
    private ?string $articuloDetalle = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $articuloModelo = null;
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $articuloMarca = null;
    
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $articuloImpuesto = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $cantidad = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $precioUnitario = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $clienteDescuento = '0';

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
            $this->setArticuloCodigo($articulo->getCodigo());
            $this->setArticuloDetalle($articulo->getDetalle());
            $this->setArticuloModelo($articulo->getModelo());
            $this->setArticuloMarca($articulo->getMarca() ? $articulo->getMarca()->getNombre() : null);
            $this->setArticuloImpuesto($articulo->getImpuesto());
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

    public function getClienteDescuento(): ?string
    {
        return $this->clienteDescuento;
    }

    public function setClienteDescuento(string $clienteDescuento): static
    {
        $this->clienteDescuento = $clienteDescuento;
        return $this;
    }

    public function getSubtotal(): float
    {
        if (!$this->cantidad || !$this->precioUnitario) {
            return 0;
        }

        $subtotal = floatval($this->cantidad) * floatval($this->precioUnitario);
        /**
         * @todo Revisar si es necesario, porque el descuento se aplicar en el servicio de precios
         * 
         */
        // if ($this->clienteDescuento) {
        //     $descuento = $subtotal * (floatval($this->clienteDescuento) / 100);
        //     $subtotal -= $descuento;
        // }
        
        return $subtotal;
    }

    public function __toString(): string
    {
        return $this->articuloDetalle ?: ($this->articulo ? $this->articulo->getDetalle() : '');
    }
    
    public function getArticuloCodigo(): ?string
    {
        return $this->articuloCodigo;
    }

    public function setArticuloCodigo(?string $articuloCodigo): static
    {
        $this->articuloCodigo = $articuloCodigo;
        return $this;
    }

    public function getArticuloDetalle(): ?string
    {
        return $this->articuloDetalle;
    }

    public function setArticuloDetalle(string $articuloDetalle): static
    {
        $this->articuloDetalle = $articuloDetalle;
        return $this;
    }

    public function getArticuloModelo(): ?string
    {
        return $this->articuloModelo;
    }

    public function setArticuloModelo(?string $articuloModelo): static
    {
        $this->articuloModelo = $articuloModelo;
        return $this;
    }
    
    public function getArticuloMarca(): ?string
    {
        return $this->articuloMarca;
    }

    public function setArticuloMarca(?string $articuloMarca): static
    {
        $this->articuloMarca = $articuloMarca;
        return $this;
    }
    
    public function getArticuloImpuesto(): ?string
    {
        return $this->articuloImpuesto;
    }

    public function setArticuloImpuesto(?string $articuloImpuesto): static
    {
        $this->articuloImpuesto = $articuloImpuesto;
        return $this;
    }
}