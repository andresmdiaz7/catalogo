<?php

namespace App\Entity;

use App\Repository\ArchivoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Archivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $file_name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $file_path = null;

    #[ORM\Column(length: 100)]
    private ?string $tipoMime = null;
    
    #[ORM\Column]
    private ?int $tamanio = null;
    
    #[ORM\OneToMany(mappedBy: 'archivo', targetEntity: ArticuloArchivo::class, orphanRemoval: true)]
    private Collection $articuloArchivos;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hash = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $fechaCreacion = null;

    public function __construct()
    {
        $this->articuloArchivos = new ArrayCollection();
        $this->fechaCreacion = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setFechaCreacionValue(): void
    {
        $this->fechaCreacion = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    /**
     * @param string $fileName
     * @return self
     */
    public function setFileName(string $fileName): self
    {
        $this->file_name = $fileName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    /**
     * @param string $filePath
     * @return self
     */
    public function setFilePath(string $filePath): self
    {
        $this->file_path = $filePath;
        return $this;
    }

    public function getTipoMime(): ?string
    {
        return $this->tipoMime;
    }

    public function setTipoMime(string $tipoMime): self
    {
        $this->tipoMime = $tipoMime;
        return $this;
    }
    
    public function getTamanio(): ?int
    {
        return $this->tamanio;
    }
    
    public function setTamanio(int $tamanio): self
    {
        $this->tamanio = $tamanio;
        return $this;
    }
    
    public function getHash(): ?string
    {
        return $this->hash;
    }
    
    public function setHash(?string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getFechaCreacion(): ?\DateTimeInterface
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTimeInterface $fechaCreacion): self
    {
        $this->fechaCreacion = $fechaCreacion;
        return $this;
    }

    /**
     * Obtiene la fecha de creación formateada
     */
    public function getFechaCreacionFormateada(string $formato = 'd/m/Y H:i'): string
    {
        return $this->fechaCreacion ? $this->fechaCreacion->format($formato) : '';
    }

    /**
     * @return Collection<int, ArticuloArchivo>
     */
    public function getArticuloArchivos(): Collection
    {
        return $this->articuloArchivos;
    }

    public function addArticuloArchivo(ArticuloArchivo $articuloArchivo): self
    {
        if (!$this->articuloArchivos->contains($articuloArchivo)) {
            $this->articuloArchivos->add($articuloArchivo);
            $articuloArchivo->setArchivo($this);
        }

        return $this;
    }

    public function removeArticuloArchivo(ArticuloArchivo $articuloArchivo): self
    {
        if ($this->articuloArchivos->removeElement($articuloArchivo)) {
            // set the owning side to null (unless already changed)
            if ($articuloArchivo->getArchivo() === $this) {
                $articuloArchivo->setArchivo(null);
            }
        }

        return $this;
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->tipoMime, 'image/');
    }

    public function getTamanioFormateado(): string
    {
        if ($this->tamanio >= 1048576) {
            return round($this->tamanio / 1048576, 2) . ' MB';
        } elseif ($this->tamanio >= 1024) {
            return round($this->tamanio / 1024, 2) . ' KB';
        } else {
            return $this->tamanio . ' bytes';
        }
    }
    
    /**
     * Obtiene el tipo de icono según el tipo MIME
     */
    public function getTipoIcono(): string
    {
        if (str_starts_with($this->tipoMime, 'image/')) {
            return 'bi-file-image';
        } elseif (str_starts_with($this->tipoMime, 'application/pdf')) {
            return 'bi-file-pdf';
        } elseif (str_starts_with($this->tipoMime, 'application/msword') || 
                  str_starts_with($this->tipoMime, 'application/vnd.openxmlformats-officedocument.wordprocessingml')) {
            return 'bi-file-word';
        } elseif (str_starts_with($this->tipoMime, 'application/vnd.ms-excel') || 
                  str_starts_with($this->tipoMime, 'application/vnd.openxmlformats-officedocument.spreadsheetml')) {
            return 'bi-file-excel';
        } else {
            return 'bi-file';
        }
    }
    
    /**
     * Obtiene la URL completa del archivo
     */
    public function getUrlArchivo(): string
    {
        return '/uploads/archivos/' . $this->file_path;
    }
}