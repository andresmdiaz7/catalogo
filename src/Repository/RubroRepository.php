<?php

namespace App\Repository;

use App\Entity\Rubro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RubroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rubro::class);
    }

    public function findOneByNombreSimilar(string $nombre): ?\App\Entity\Rubro
    {
        return $this->createQueryBuilder('r')
            ->where('LOWER(r.nombre) LIKE :nombre')
            ->setParameter('nombre', '%' . mb_strtolower($nombre) . '%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 