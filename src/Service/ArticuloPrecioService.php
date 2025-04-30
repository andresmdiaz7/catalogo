<?php

namespace App\Service;

use App\Entity\Articulo;
use App\Entity\Cliente;
use App\Repository\ArticuloRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;
use App\Service\ClienteManager;

class ArticuloPrecioService
{
    private $user;
    private ?Cliente $clienteActivo = null;

    public function __construct(
        private ArticuloRepository $articuloRepository,
        private Security $security,
        private ClienteManager $clienteManager
    ) {
        $this->user = $this->security->getUser();
    }

    /**
     * Obtiene el cliente activo de forma perezosa (lazy loading)
     * @return Cliente|null
     */
    private function getClienteActivo(): ?Cliente
    {
        // Solo obtenemos el cliente la primera vez que se necesita
        if ($this->clienteActivo === null && $this->user && $this->security->isGranted('ROLE_CLIENTE')) {
            $this->clienteActivo = $this->clienteManager->getClienteActivo();
        }
        return $this->clienteActivo;
    }

    /**
     * Obtiene el precio base de un artículo
     * @param Articulo $articulo
     * @return float
     */
    public function getPrecioBase(Articulo $articulo): float
    {
        $clienteActivo = $this->getClienteActivo();
        if ($clienteActivo && $clienteActivo->getTipoCliente()->getNombre() === 'Mayorista') {
            return $articulo->getPrecio400();
        }
        
        return $articulo->getPrecioLista();
    }

    /**
     * Obtiene el precio sin IVA de un artículo
     * @param Articulo $articulo
     * @return float
     */
    public function getPrecioSinIVA(Articulo $articulo): float
    {
        $precioBase = $this->getPrecioBase($articulo);
        $calculo = $precioBase / (1 + ($articulo->getImpuesto() / 100));
        return number_format($calculo, 2, '.', '');
    }

    public function getPrecioConDescuento(Articulo $articulo): float
    {
        $clienteActivo = $this->getClienteActivo();
        if (!$clienteActivo) {
            return 0;
        }
        
        $descuento = $clienteActivo->getPorcentajeDescuento();
        $calculo = $this->getPrecioSinIVA($articulo) * (1 - ($descuento / 100));
        return number_format($calculo, 2, '.', '');
    }

    /**
     * Obtiene el precio con descuento y rentabilidad de un artículo
     * @param Articulo $articulo
     * @return float
     */
    public function getPrecioConDescuentoyRentabilidad(Articulo $articulo): float
    {
        $clienteActivo = $this->getClienteActivo();
        if (!$clienteActivo) {
            return 0;
        }
        
        $recargo = $clienteActivo->getRentabilidad();
        $calculo = $this->getPrecioConDescuento($articulo) * (1 + ($recargo / 100));
        return number_format($calculo, 2, '.', '');
    }

    /**
     * Obtiene el precio final de un artículo
     * @param Articulo $articulo
     * @return float
     */
    public function getPrecioFinal(Articulo $articulo): float
    {
        if (!$this->getClienteActivo()) {
            return $this->getPrecioBase($articulo);
        }
        $impuesto = $articulo->getImpuesto();
        
        $calculo = $this->getPrecioConDescuentoyRentabilidad($articulo) * (1 + ($impuesto / 100));
        return number_format($calculo, 2, '.', '');
    }

    /**
     * Obtiene todos los precios de un artículo
     * @param Articulo $articulo
     * @return array
     */
    public function getTodosLosPrecios(Articulo $articulo): array
    {
        return [
            'precioBase' => $this->getPrecioBase($articulo),
            'precioBaseSIVA' => $this->getPrecioSinIVA($articulo),
            'precioConDescuento' => $this->getPrecioConDescuento($articulo),
            'precioConDescuentoRentabilidad' => $this->getPrecioConDescuentoyRentabilidad($articulo),
            'precioFinal' => $this->getPrecioFinal($articulo)
        ];
    }

    /**
     * Método para invalidar el cliente activo en caché (útil si cambia durante la ejecución)
     */
    public function resetClienteActivo(): void
    {
        $this->clienteActivo = null;
    }
} 