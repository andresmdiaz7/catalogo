<?php
// src/Repository/TipoUsuarioRepository.php

namespace App\Repository;

use App\Entity\TipoUsuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TipoUsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipoUsuario::class);
    }

    public function findByCodigo(string $codigo): ?TipoUsuario
    {
        return $this->findOneBy(['codigo' => $codigo]);
    }

    public function findActivos()
    {
        return $this->createQueryBuilder('t')
            ->where('t.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('t.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
