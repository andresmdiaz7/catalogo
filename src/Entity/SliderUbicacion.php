<?php
namespace App\Entity;

use App\Repository\SliderUbicacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SliderUbicacionRepository::class)]
class SliderUbicacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 50)]
    private ?string $codigo = null;

    #[ORM\Column]
    private ?int $anchoMaximo = null;

    #[ORM\Column]
    private ?int $altoMaximo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private ?bool $activo = true;

    #[ORM\OneToMany(mappedBy: 'ubicacion', targetEntity: Slider::class)]
    private Collection $sliders;

    public function __construct()
    {
        $this->sliders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
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

    public function getAnchoMaximo(): ?int
    {
        return $this->anchoMaximo;
    }

    public function setAnchoMaximo(int $anchoMaximo): self
    {
        $this->anchoMaximo = $anchoMaximo;
        return $this;
    }

    public function getAltoMaximo(): ?int
    {
        return $this->altoMaximo;
    }

    public function setAltoMaximo(int $altoMaximo): self
    {
        $this->altoMaximo = $altoMaximo;
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

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        return $this;
    }

    /**
     * @return Collection<int, Slider>
     */
    public function getSliders(): Collection
    {
        return $this->sliders;
    }

    public function addSlider(Slider $slider): self
    {
        if (!$this->sliders->contains($slider)) {
            $this->sliders->add($slider);
            $slider->setUbicacion($this);
        }

        return $this;
    }

    public function removeSlider(Slider $slider): self
    {
        if ($this->sliders->removeElement($slider)) {
            // set the owning side to null (unless already changed)
            if ($slider->getUbicacion() === $this) {
                $slider->setUbicacion(null);
            }
        }

        return $this;
    }
}
