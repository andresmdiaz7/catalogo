<?php

namespace App\Entity;

use App\Repository\CarritoItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarritoItemRepository::class)]
#[ORM\Table(name: 'carrito_item')]
class CarritoItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Carrito $carrito = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'codigo')]
    private ?Articulo $articulo = null;

    #[ORM\Column(type: 'integer')]
    private int $cantidad = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $precioUnitario = null;
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCarrito(): ?Carrito
    {
        return $this->carrito;
    }

    public function setCarrito(?Carrito $carrito): static
    {
        $this->carrito = $carrito;

        return $this;
    }

    public function getArticulo(): ?Articulo
    {
        return $this->articulo;
    }

    public function setArticulo(?Articulo $articulo): static
    {
        $this->articulo = $articulo;

        return $this;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): static
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

    public function getTotal(): float
    {
        return $this->cantidad * (float)$this->precioUnitario;
    }

}
