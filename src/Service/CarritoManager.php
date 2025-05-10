<?php

namespace App\Service;

use App\Entity\Articulo;
use App\Entity\Carrito;
use App\Entity\CarritoItem;
use App\Entity\Cliente;
use App\Entity\Pedido;
use App\Entity\PedidoDetalle;
use App\Entity\EstadoPedido;
use App\Repository\CarritoItemRepository;
use App\Repository\CarritoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CarritoManager
{
    private $session;
    private $clienteManager;
    private $entityManager;
    private $carritoRepository;
    private $carritoItemRepository;
    private $articuloPrecioService;
    private $emailService;

    public function __construct(
        RequestStack $requestStack,
        ClienteManager $clienteManager,
        EntityManagerInterface $entityManager,
        CarritoRepository $carritoRepository,
        CarritoItemRepository $carritoItemRepository,
        ArticuloPrecioService $articuloPrecioService,
        EmailService $emailService
    ) {
        $this->session = $requestStack->getSession();
        $this->clienteManager = $clienteManager;
        $this->entityManager = $entityManager;
        $this->carritoRepository = $carritoRepository;
        $this->carritoItemRepository = $carritoItemRepository;
        $this->articuloPrecioService = $articuloPrecioService;
        $this->emailService = $emailService;
    }

    /**
     * Añade un artículo al carrito de compras
     * @param Articulo $articulo
     * @param int $cantidad
     */
    public function agregar(Articulo $articulo, int $cantidad = 0): void
    {
        $carrito = $this->session->get('carrito', []);
        $codigo = $articulo->getCodigo();
        
        if (!isset($carrito[$codigo])) {
            $carrito[$codigo] = [
                'codigo' => $codigo,
                'detalle' => $articulo->getDetalle(),
                'marca' => $articulo->getMarca(),
                'modelo' => $articulo->getModelo(), 
                'precio' => $articulo->getPrecios()['precioFinal'],
                'cantidad' => 0
            ];
        }
        
        $carrito[$codigo]['cantidad'] += $cantidad;
        
        $this->session->set('carrito', $carrito);
    }

    /**
     * Elimina un artículo del carrito de compras
     * @param Articulo $articulo
     */
    public function eliminar(Articulo $articulo): void
    {
        $carrito = $this->session->get('carrito', []);
        $codigo = $articulo->getCodigo();


        if (isset($carrito[$codigo])) {
            unset($carrito[$codigo]);
            $this->session->set('carrito', $carrito);
        }

    }

    /**
     * Elimina todos los artículos del carrito de compras del cliente actual
     */
    public function limpiar(): void
    {
        $carrito = $this->obtenerCarritoActivo();
        if ($carrito) {
            foreach ($carrito->getItems() as $item) {
                $this->entityManager->remove($item);
            }
            $this->entityManager->remove($carrito);
            $this->entityManager->flush();
        }
    }
    
    /**
     * Obtiene un item específico del carrito del cliente actual, usando en el controlador de carrito para eliminar un item
     * @param int $id ID del item a buscar
     * @return CarritoItem|null El item del carrito si existe, null en caso contrario
     */
    public function getItem(int $id): ?CarritoItem
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito) {
            return null;
        }

        return $this->carritoItemRepository->findOneBy([
            'carrito' => $carrito,
            'id' => $id
        ]);
    }

    /**
     * Obtiene los artículos del carrito de compras
     * @return array
     */
    public function getItems(): array
    {
        return $this->session->get('carrito', []);
    }

    /**
     * Obtiene la cantidad total de items en el carrito activo
     * @return int Cantidad total de items
     */
    public function getItemsCantidadTotal(): int
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito) {
            return 0;
        }

        $totalCantidad = 0;
        foreach ($carrito->getItems() as $item) {
            $totalCantidad += $item->getCantidad();
        }
        return $totalCantidad;
    }
    /**
     * Obtiene el total del carrito de compras
     * @return float
     */
    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        return $total;

    }

    public function obtenerCarritoActivo(): ?Carrito
    {
        $cliente = $this->clienteManager->getClienteActivo();
        if (!$cliente) {
            return null;
        }

        return $this->obtenerCarritoDeCliente($cliente);
    }

    public function obtenerCarritoDeCliente(Cliente $cliente): Carrito
    {
        // Buscar carrito activo existente
        $carrito = $this->carritoRepository->findOneBy([
            'cliente' => $cliente,
            'estado' => 'activo'
        ]);

        // Si no existe, crear uno nuevo
        if (!$carrito) {
            $carrito = new Carrito();
            $carrito->setCliente($cliente);
            $this->entityManager->persist($carrito);
            $this->entityManager->flush();
        }

        return $carrito;
    }

    public function agregarArticulo(Articulo $articulo, int $cantidad = 1): CarritoItem
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito) {
            throw new \Exception('No hay cliente activo');
        }

        // Verificar si el artículo ya está en el carrito
        foreach ($carrito->getItems() as $item) {
            if ($item->getArticulo()->getCodigo() === $articulo->getCodigo()) {
                // Incrementar cantidad
                $item->setCantidad($item->getCantidad() + $cantidad);
                $this->actualizarCarrito($carrito);
                return $item;
            }
        }

        // Si no existe, crear nuevo item
        $cliente = $this->clienteManager->getClienteActivo();
        $precioUnitario = $this->articuloPrecioService->getPrecioFinal($articulo);

        $item = new CarritoItem();
        $item->setCarrito($carrito);
        $item->setArticulo($articulo);
        $item->setCantidad($cantidad);
        $item->setPrecioUnitario($precioUnitario);

        $carrito->addItem($item);
        $this->actualizarCarrito($carrito);
        
        return $item;
    }

    public function eliminarItem(CarritoItem $item): void
    {
        $carrito = $item->getCarrito();
        $carrito->removeItem($item);
        
        $this->entityManager->remove($item);
        $this->actualizarCarrito($carrito);
    }

    public function eliminarArticulo(Articulo $articulo): void
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito) {
            return;
        }

        foreach ($carrito->getItems() as $item) {
            if ($item->getArticulo()->getId() === $articulo->getId()) {
                $this->eliminarItem($item);
                break;
            }
        }
    }

    public function actualizarCantidad(CarritoItem $item, int $cantidad): void
    {
        if ($cantidad <= 0) {
            $this->eliminarItem($item);
            return;
        }

        $item->setCantidad($cantidad);
        $this->actualizarCarrito($item->getCarrito());
    }

    public function vaciarCarrito(): void
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito) {
            return;
        }

        foreach ($carrito->getItems()->toArray() as $item) {
            $this->entityManager->remove($item);
        }
        
        $carrito->getItems()->clear();
        $this->actualizarCarrito($carrito);
    }

    private function actualizarCarrito(Carrito $carrito): void
    {
        $carrito->setFechaActualizacion(new \DateTime());
        $this->entityManager->flush();
    }

    public function convertirAPedido(): ?Pedido
    {
        $carrito = $this->obtenerCarritoActivo();
        if (!$carrito || $carrito->getItems()->isEmpty()) {
            return null;
        }

        $cliente = $this->clienteManager->getClienteActivo();
        if (!$cliente) {
            throw new \Exception('No hay cliente activo');
        }

        // Crear nuevo pedido
        $pedido = new Pedido();
        $pedido->setCliente($cliente);
        $pedido->setFecha(new \DateTime());
        $pedido->setEstado(EstadoPedido::PENDIENTE);
        
        // Agregar los items del carrito al pedido
        $total = 0;
        foreach ($carrito->getItems() as $item) {
            $pedidoDetalle = new PedidoDetalle();
            $pedidoDetalle->setPedido($pedido);
            $pedidoDetalle->setArticulo($item->getArticulo());
            $pedidoDetalle->setCantidad($item->getCantidad());
            $pedidoDetalle->setPrecioUnitario($item->getPrecioUnitario());
            
            // Calcular subtotal del item
            $subtotal = $item->getCantidad() * $item->getPrecioUnitario();
            $total += $subtotal;
            
            $pedido->addDetalle($pedidoDetalle);
        }
        
        // Establecer el total del pedido
        $pedido->setTotal($total);
        
        // Persistir el pedido
        $this->entityManager->persist($pedido);
        
        // Marcar el carrito como convertido
        $carrito->setEstado('convertido');
        
        $this->entityManager->flush();
        
        // Enviar notificaciones por email
        //$this->enviarNotificacionesPedido($pedido);
        
        return $pedido;
    }

    private function enviarNotificacionesPedido(Pedido $pedido): void
    {
        // Notificar al cliente
        $this->emailService->enviarConfirmacionPedidoCliente($pedido);
        
        // Notificar al vendedor
        $vendedor = $pedido->getCliente()->getVendedor();
        if ($vendedor) {
            $this->emailService->enviarNotificacionPedidoVendedor($pedido, $vendedor);
        }
        
        // Notificar al responsable de logística
        $responsableLogistica = $pedido->getCliente()->getResponsableLogistica();
        if ($responsableLogistica) {
            $this->emailService->enviarNotificacionPedidoLogistica($pedido, $responsableLogistica);
        }
    }

    public function getClienteManager(): ClienteManager
    {
        return $this->clienteManager;
    }

    // Más métodos a implementar en las siguientes etapas...
}
