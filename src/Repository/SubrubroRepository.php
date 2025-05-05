<?php

namespace App\Repository;

use App\Entity\Subrubro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubrubroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subrubro::class);
    }
    
    public function findOneByNombreSimilar(string $nombre): ?\App\Entity\Subrubro
    {
        return $this->createQueryBuilder('sr')
            ->where('LOWER(sr.nombre) LIKE :nombre')
            ->setParameter('nombre', '%' . mb_strtolower($nombre) . '%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 