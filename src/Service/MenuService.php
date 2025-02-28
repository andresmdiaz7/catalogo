<?php
namespace App\Service;

use App\Entity\Cliente;
use App\Entity\Menu;
use App\Entity\MenuSeccion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security; // Corregido el namespace

class MenuService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function obtenerMenuDisponible(): Menu
    {
        $user = $this->security->getUser();
        
        if ($user instanceof Cliente && $user->getCategoria()?->getMenu()) {
            return $user->getCategoria()->getMenu();
        }

        return $this->entityManager->getRepository(Menu::class)
            ->findOneBy(['porDefecto' => true, 'activo' => true]);
    }

    public function obtenerSeccionesMenu(Menu $menu): array
    {
        return $this->entityManager->getRepository(MenuSeccion::class)
            ->createQueryBuilder('ms')
            ->join('ms.seccion', 's')
            ->where('ms.menu = :menu')
            ->andWhere('ms.activo = :activo')
            ->setParameter('menu', $menu)
            ->setParameter('activo', true)
            ->orderBy('ms.orden', 'ASC')
            ->getQuery()
            ->getResult();
    }
}