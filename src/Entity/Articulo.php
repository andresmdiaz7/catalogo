<?php

namespace App\Entity;

use App\Repository\ArticuloRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticuloRepository::class)]
class Articulo
{
    
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $codigo = null;

    #[ORM\Column(length: 255)]
    private ?string $detalle = null;

    #[ORM\ManyToOne(inversedBy: 'articulos', cascade: ['all'])]
    #[ORM\JoinColumn(name: 'subrubro_codigo', referencedColumnName: 'codigo', nullable: false)]
    private ?Subrubro $subrubro = null;


    #[ORM\ManyToOne(inversedBy: 'articulos')]
    #[ORM\JoinColumn(name: 'marca_codigo', referencedColumnName: 'codigo', nullable: true)]
    private ?Marca $marca = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $modelo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $detalleWeb = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $impuesto = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $precioLista = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $precio400 = null;

    #[ORM\Column(type: 'boolean')]
    private bool $destacado = false;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitadoWeb = true;

    #[ORM\Column(type: 'boolean')]
    private bool $habilitadoGestion = true;

    #[ORM\Column(type: 'boolean')]
    private bool $novedad = false;

    
    #[ORM\OneToMany(mappedBy: 'articulo', targetEntity: ArticuloArchivo::class)]
    private Collection $archivos;

    private $precios;

    public function __construct()
    {
        $this->archivos = new ArrayCollection();
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

    public function getDetalle(): ?string
    {
        return $this->detalle;
    }

    public function setDetalle(string $detalle): self
    {
        $this->detalle = $detalle;
        return $this;
    }

    public function getSubrubro(): ?Subrubro
    {
        return $this->subrubro;
    }

    public function setSubrubro(?Subrubro $subrubro): self
    {
        $this->subrubro = $subrubro;
        return $this;
    }

    public function getMarca(): ?Marca
    {
        return $this->marca;
    }

    public function setMarca(?Marca $marca): self
    {
        $this->marca = $marca;
        return $this;
    }

    public function getModelo(): ?string
    {
        return $this->modelo;
    }

    public function setModelo(?string $modelo): self
    {
        $this->modelo = $modelo;
        return $this;
    }

    public function getDetalleWeb(): ?string
    {
        return $this->detalleWeb;
    }

    public function setDetalleWeb(?string $detalleWeb): self
    {
        $this->detalleWeb = $detalleWeb;
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

    public function getImpuesto(): ?float
    {
        return $this->impuesto;
    }

    public function setImpuesto(float $impuesto): self
    {
        $this->impuesto = $impuesto;
        return $this;
    }

    public function getPrecioLista(): ?float
    {
        return $this->precioLista;
    }

    public function setPrecioLista(float $precioLista): self
    {
        $this->precioLista = $precioLista;
        return $this;
    }

    public function getPrecio400(): ?float
    {
        return $this->precio400;
    }

    public function setPrecio400(float $precio400): self
    {
        $this->precio400 = $precio400;
        return $this;
    }

    public function isDestacado(): bool
    {
        return $this->destacado;
    }

    public function setDestacado(bool $destacado): self
    {
        $this->destacado = $destacado;
        return $this;
    }

    public function isHabilitadoWeb(): bool
    {
        return $this->habilitadoWeb;
    }

    public function setHabilitadoWeb(bool $habilitadoWeb): self
    {
        $this->habilitadoWeb = $habilitadoWeb;
        return $this;
    }

    public function isHabilitadoGestion(): bool
    {
        return $this->habilitadoGestion;
    }

    public function setHabilitadoGestion(bool $habilitadoGestion): self
    {
        $this->habilitadoGestion = $habilitadoGestion;
        return $this;
    }

    public function isNovedad(): bool
    {
        return $this->novedad;
    }

    public function setNovedad(bool $novedad): self
    {
        $this->novedad = $novedad;
        return $this;
    }

    /**
     * @return Collection<int, ArticuloArchivo>
     */
    public function getArchivos(): Collection
    {
        return $this->archivos;
    }

    public function addArchivo(ArticuloArchivo $archivo): static
    {
        if (!$this->archivos->contains($archivo)) {
            $this->archivos->add($archivo);
            $archivo->setArticulo($this);
        }
        return $this;
    }

    public function removeArchivo(ArticuloArchivo $archivo): static
    {
        if ($this->archivos->removeElement($archivo)) {
            if ($archivo->getArticulo() === $this) {
                $archivo->setArticulo(null);
            }
        }
        return $this;
    }

    public function getImagenPrincipal(): ?ArticuloArchivo
    {
        foreach ($this->archivos as $archivo) {
            if ($archivo->isEsPrincipal()) {
                return $archivo;
            }
        }
        return $this->archivos->first() ?: null;
    }

    public function setPrecios(array $precios): self
    {
        $this->precios = $precios;
        return $this;
    }
    
    public function getPrecios(): array
    {
        return $this->precios ?? [];
    }
}