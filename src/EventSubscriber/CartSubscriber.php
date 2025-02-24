<?php

namespace App\EventSubscriber;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class CartSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CartService $cartService,
        private Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $cartItems = $this->cartService->getItems();
        $totalQuantity = array_sum(array_column($cartItems, 'cantidad'));
        $this->twig->addGlobal('carrito_articulos_totales', $totalQuantity);
        $this->twig->addGlobal('carrito_articulos', $cartItems);
    }
}