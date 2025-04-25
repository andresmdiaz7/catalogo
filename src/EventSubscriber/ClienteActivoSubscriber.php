<?php

namespace App\EventSubscriber;

use App\Service\ClienteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClienteActivoSubscriber implements EventSubscriberInterface
{
    private $security;
    private $clienteManager;
    private $urlGenerator;

    public function __construct(
        Security $security,
        ClienteManager $clienteManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->security = $security;
        $this->clienteManager = $clienteManager;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Excluir rutas específicas (login, logout, selección de cliente)
        $excludedRoutes = [
            'app_login', 
            'app_logout', 
            'app_cliente_seleccionar', 
            '_wdt', 
            '_profiler'
        ];
        
        $currentRoute = $request->attributes->get('_route');
        
        if (in_array($currentRoute, $excludedRoutes)) {
            return;
        }
        
        // Solo verificar rutas bajo el prefijo /cliente
        if (!str_starts_with($currentRoute, 'app_cliente_')) {
            return;
        }
        
        // Si el usuario tiene ROLE_CLIENTE pero no tiene cliente activo
        if ($this->security->isGranted('ROLE_CLIENTE') && !$this->clienteManager->hasClienteActivo()) {
            // Intentar configurar automáticamente
            if (!$this->clienteManager->configurarClienteActivoAutomaticamente()) {
                // Redirigir a la selección de cliente
                $response = new RedirectResponse($this->urlGenerator->generate('app_cliente_seleccionar'));
                $event->setResponse($response);
            }
        }
    }
}
