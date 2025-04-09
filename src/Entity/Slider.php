<?php
namespace App\Entity;

use App\Repository\SliderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SliderRepository::class)]
class Slider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $titulo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlDestino = null;

    #[ORM\Column]
    private ?bool $activo = true;

    #[ORM\ManyToOne]
    private ?Categoria $categoria = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fechaInicio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fechaFin = null;

    #[ORM\Column]
    private ?int $orden = 0;

    #[ORM\ManyToOne(inversedBy: 'sliders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SliderUbicacion $ubicacion = null;

    #[ORM\OneToMany(mappedBy: 'slider', targetEntity: SliderArchivo::class, orphanRemoval: true)]
    #[ORM\OrderBy(['orden' => 'ASC'])]
    private Collection $archivos;

    public function __construct()
    {
        $this->archivos = new ArrayCollection();
        $this->fechaInicio = new \DateTime();
        $this->fechaFin = new \DateTime('+1 year');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getUrlDestino(): ?string
    {
        return $this->urlDestino;
    }

    public function setUrlDestino(?string $urlDestino): self
    {
        $this->urlDestino = $urlDestino;
        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
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

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fechaInicio;
    }

    public function setFechaInicio(\DateTimeInterface $fechaInicio): self
    {
        $this->fechaInicio = $fechaInicio;
        return $this;
    }

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fechaFin;
    }

    public function setFechaFin(\DateTimeInterface $fechaFin): self
    {
        $this->fechaFin = $fechaFin;
        return $this;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): self
    {
        $this->orden = $orden;
        return $this;
    }

    public function getUbicacion(): ?SliderUbicacion
    {
        return $this->ubicacion;
    }

    public function setUbicacion(?SliderUbicacion $ubicacion): self
    {
        $this->ubicacion = $ubicacion;
        return $this;
    }

    /**
     * @return Collection<int, SliderArchivo>
     */
    public function getArchivos(): Collection
    {
        return $this->archivos;
    }

    public function addArchivo(SliderArchivo $archivo): self
    {
        if (!$this->archivos->contains($archivo)) {
            $this->archivos->add($archivo);
            $archivo->setSlider($this);
        }

        return $this;
    }

    public function removeArchivo(SliderArchivo $archivo): self
    {
        if ($this->archivos->removeElement($archivo)) {
            // set the owning side to null (unless already changed)
            if ($archivo->getSlider() === $this) {
                $archivo->setSlider(null);
            }
        }

        return $this;
    }
}
