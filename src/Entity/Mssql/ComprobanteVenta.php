<?php

namespace App\Entity\Mssql;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "Comprobantes_Ventas")]
class ComprobanteVenta
{
    #[ORM\Id]
    #[ORM\Column(name: "ID", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "CODIGO", type: "integer")]
    private ?int $codigo = null;

    #[ORM\Column(name: "Tipo_Operacion", type: "string", length: 50)]
    private ?string $tipoOperacion = null;

    #[ORM\Column(name: "Pendiente", type: "decimal", precision: 15, scale: 2)]
    private ?string $pendiente = null;

    // Getters y Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?int
    {
        return $this->codigo;
    }

    public function setCodigo(int $codigo): self
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getTipoOperacion(): ?string
    {
        return $this->tipoOperacion;
    }

    public function setTipoOperacion(string $tipoOperacion): self
    {
        $this->tipoOperacion = $tipoOperacion;
        return $this;
    }

    public function getPendiente(): ?string
    {
        return $this->pendiente;
    }

    public function setPendiente(string $pendiente): self
    {
        $this->pendiente = $pendiente;
        return $this;
    }
}
