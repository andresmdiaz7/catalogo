<?php
namespace App\Repository;

use App\Entity\SliderUbicacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SliderUbicacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SliderUbicacion::class);
    }

    public function findAllActivos(): array
    {
        return $this->createQueryBuilder('su')
            ->where('su.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('su.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
