<?php

namespace App\EventListener;

use App\Entity\Articulo;
use App\Service\ArticuloPrecioService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postLoad, entity: Articulo::class)]
class ArticuloPreciosListener
{
    public function __construct(
        private ArticuloPrecioService $articuloPrecioService
    ) {}

    public function postLoad(Articulo $articulo, PostLoadEventArgs $event): void
    {
        $precios = $this->articuloPrecioService->getTodosLosPrecios($articulo);
        $articulo->setPrecios($precios); // Necesitarás agregar este método a la entidad Articulo
    }
}