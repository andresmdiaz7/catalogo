<?php

namespace App\Entity;

use App\Repository\CarritoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarritoRepository::class)]
#[ORM\Table(name: 'carrito')]
class Carrito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'carritos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cliente $cliente = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaCreacion;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaActualizacion;

    #[ORM\Column(length: 20)]
    private string $estado = 'activo'; // activo, abandonado, convertido

    #[ORM\OneToMany(
        mappedBy: 'carrito', 
        targetEntity: CarritoItem::class, 
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->fechaCreacion = new \DateTime();
        $this->fechaActualizacion = new \DateTime();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): static
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getFechaCreacion(): \DateTimeInterface
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTimeInterface $fechaCreacion): static
    {
        $this->fechaCreacion = $fechaCreacion;

        return $this;
    }

    public function getFechaActualizacion(): \DateTimeInterface
    {
        return $this->fechaActualizacion;
    }

    public function setFechaActualizacion(\DateTimeInterface $fechaActualizacion): static
    {
        $this->fechaActualizacion = $fechaActualizacion;

        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    /**
     * @return Collection<int, CarritoItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CarritoItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCarrito($this);
        }

        return $this;
    }

    public function removeItem(CarritoItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCarrito() === $this) {
                $item->setCarrito(null);
            }
        }

        return $this;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getTotal();
        }
        return $total;
    }

}
