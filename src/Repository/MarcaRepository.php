<?php

namespace App\Repository;

use App\Entity\Marca;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MarcaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Marca::class);
    }

    /**
     * @return Marca[] Devuelve todas las marcas habilitadas
     */
    public function findAllEnabled(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.habilitado = :habilitado')
            ->setParameter('habilitado', true)
            ->orderBy('m.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}