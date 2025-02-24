<?php

namespace App\EventSubscriber;

use App\Repository\SeccionRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SeccionRepository $seccionRepository,
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
        $secciones = $this->seccionRepository->createQueryBuilder('s')
            ->where('s.habilitado = :habilitado')
            ->setParameter('habilitado', true)
            ->orderBy('s.orden', 'ASC')
            ->getQuery()
            ->getResult();
        $this->twig->addGlobal('menu_secciones', $secciones);
    }
} 