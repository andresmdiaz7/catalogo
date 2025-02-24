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
            $cliente = $this->user;
            return $cliente->getTipoCliente()->getNombre() === 'Mayorista' 
                ? $articulo->getPrecio400() 
                : $articulo->getPrecioLista();
        }
        
        return $articulo->getPrecioLista();
    }


    public function getPrecioSinIVACalculado(Articulo $articulo): float
    {
        $precioBase = $this->obtenerPrecioBase($articulo);
        return $precioBase / (1 + ($articulo->getImpuesto() / 100));
    }

    public function getPrecioConDescuentoCalculado(Articulo $articulo): float
    {
        if (!$this->user or !$this->security->isGranted('ROLE_CLIENTE')) {
            return 0;
        }
        
        $descuento = $this->user->getPorcentajeDescuento();
        return $this->getPrecioSinIVACalculado($articulo) * (1 - ($descuento / 100));
    }

    public function precioConDescuentoyRecargo(Articulo $articulo): float
    {
        if (!$this->user or !$this->security->isGranted('ROLE_CLIENTE')) {
            return 0;
        }
        
        $recargo = $this->user->getRentabilidad();
        return $this->getPrecioConDescuentoCalculado($articulo) * (1 + ($recargo / 100));
    }

    public function getTodosLosPrecios(Articulo $articulo): array
    {
        return [
            'precioBase' => number_format($this->obtenerPrecioBase($articulo), 2, '.', ''),
            'precioSinIVA' => number_format($this->getPrecioSinIVACalculado($articulo), 2, '.', ''),
            'precioConDescuento' => number_format($this->getPrecioConDescuentoCalculado($articulo), 2, '.', ''),
            'precioConDescuentoyRecargo' => number_format($this->precioConDescuentoyRecargo($articulo), 2, '.', '')
        ];
    }
} 