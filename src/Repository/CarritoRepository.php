<?php

namespace App\Repository;

use App\Entity\Carrito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carrito>
 *
 * @method Carrito|null find($id, $lockMode = null, $lockVersion = null)
 * @method Carrito|null findOneBy(array $criteria, array $orderBy = null)
 * @method Carrito[]    findAll()
 * @method Carrito[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarritoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carrito::class);
    }

    public function findCarritosAbandonados(\DateTime $fechaLimite): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.estado = :estado')
            ->andWhere('c.fechaActualizacion < :fechaLimite')
            ->setParameter('estado', 'activo')
            ->setParameter('fechaLimite', $fechaLimite)
            ->getQuery()
            ->getResult();
    }
}

