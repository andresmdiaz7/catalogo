<?php
namespace App\Entity;

use App\Repository\SliderArchivoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SliderArchivoRepository::class)]
class SliderArchivo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'archivos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Slider $slider = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 100)]
    private ?string $tipoMime = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?int $orden = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlDestino = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textoAlternativo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePathMobile = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlider(): ?Slider
    {
        return $this->slider;
    }

    public function setSlider(?Slider $slider): self
    {
        $this->slider = $slider;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
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

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
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

    public function getUrlDestino(): ?string
    {
        return $this->urlDestino;
    }

    public function setUrlDestino(?string $urlDestino): self
    {
        $this->urlDestino = $urlDestino;
        return $this;
    }

    public function getTextoAlternativo(): ?string
    {
        return $this->textoAlternativo;
    }

    public function setTextoAlternativo(?string $textoAlternativo): self
    {
        $this->textoAlternativo = $textoAlternativo;
        return $this;
    }

    public function getFilePathMobile(): ?string
    {
        return $this->filePathMobile;
    }

    public function setFilePathMobile(?string $filePathMobile): self
    {
        $this->filePathMobile = $filePathMobile;
        return $this;
    }
}
