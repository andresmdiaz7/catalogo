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
     * Agrega el carrito activo a la variable global carrito_cliente
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $this->twig->addGlobal('carrito_cliente', $this->carritoManager->obtenerCarritoActivo());
    }
}