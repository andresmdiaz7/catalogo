<?php

namespace App\EventSubscriber;

use App\Service\CarritoManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class CarritoSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CarritoManager $carritoManager,
        private Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * Agrega la cantidad total de items en el carrito activo a la variable global carrito_articulos_totales
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $totalCantidad = $this->carritoManager->getItemsCantidadTotal();
        $this->twig->addGlobal('carrito_articulos_totales', $totalCantidad);
    }
}