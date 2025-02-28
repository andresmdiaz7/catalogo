<?php

namespace App\EventSubscriber;

use App\Service\MenuService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MenuService $menuService,
        private Environment $twig
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $menu = $this->menuService->obtenerMenuDisponible();
        $secciones = $this->menuService->obtenerSeccionesMenu($menu);

        $this->twig->addGlobal('secciones_global', $secciones);
    }
}