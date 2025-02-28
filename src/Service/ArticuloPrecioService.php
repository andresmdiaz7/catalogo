<?php

namespace App\Service;

use App\Entity\Articulo;
use App\Entity\Cliente;
use App\Repository\ArticuloRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;

class ArticuloPrecioService
{
    private $user;

    public function __construct(
        private ArticuloRepository $articuloRepository,
        private Security $security
    ) {
        $this->user = $this->security->getUser();
    }

    public function obtenerPrecioBase(Articulo $articulo): float
    {
        if ($this->user && $this->security->isGranted('ROLE_CLIENTE')) {
            return $this->user->getTipoCliente()->getNombre() === 'Mayorista' 
                ? $articulo->getPrecio400()
                : $articulo->getPrecioLista();
        }
        
        return $articulo->getPrecioLista();
    }


    public function getPrecioSinIVA(Articulo $articulo): float
    {
        $precioBase = $this->obtenerPrecioBase($articulo);
        $calculo = $precioBase / (1 + ($articulo->getImpuesto() / 100));
        return number_format($calculo, 2, '.', '');
    }

    public function getPrecioConDescuentoCalculado(Articulo $articulo): float
    {
        if (!$this->user or !$this->security->isGranted('ROLE_CLIENTE')) {
            return 0;
        }
        
        $descuento = $this->user->getPorcentajeDescuento();
        $calculo = $this->getPrecioSinIVA($articulo) * (1 - ($descuento / 100));
        return number_format($calculo, 2, '.', '');
    }

    public function precioConDescuentoyRecargo(Articulo $articulo): float
    {
        if (!$this->user or !$this->security->isGranted('ROLE_CLIENTE')) {
            return 0;
        }
        
        $recargo = $this->user->getRentabilidad();
        $calculo = $this->getPrecioConDescuentoCalculado($articulo) * (1 + ($recargo / 100));
        return number_format($calculo, 2, '.', '');
    }

    public function precioFinal(Articulo $articulo): float
    {
        if (!$this->user or !$this->security->isGranted('ROLE_CLIENTE')) {
            return 0;
        }
        $impuesto = $articulo->getImpuesto();
        
        $calculo = $this->precioConDescuentoyRecargo($articulo) * (1 + ($impuesto / 100));
        return number_format($calculo, 2, '.', '');
    }

    public function getTodosLosPrecios(Articulo $articulo): array
    {
        return [
            'precioBase' => $this->obtenerPrecioBase($articulo),
            'precioBaseSIVA' => $this->getPrecioSinIVA($articulo),
            'precioConDescuento' => $this->getPrecioConDescuentoCalculado($articulo),
            'precioConDescuentoRecargo' => $this->precioConDescuentoyRecargo($articulo),
            'precioFinal' => $this->precioFinal($articulo)
        ];
    }
} 