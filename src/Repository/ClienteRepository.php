<?php

namespace App\Repository;

use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.tipoCliente', 't')
            ->leftJoin('c.localidad', 'l')
            ->leftJoin('c.vendedor', 'v')
            ->addSelect('t', 'l', 'v');

        if (!empty($filters['tipoCliente'])) {
            $qb->andWhere('c.tipoCliente = :tipoCliente')
                ->setParameter('tipoCliente', $filters['tipoCliente']);
        }

        if (!empty($filters['localidad'])) {
            $qb->andWhere('c.localidad = :localidad')
                ->setParameter('localidad', $filters['localidad']);
        }

        if (!empty($filters['vendedor'])) {
            $qb->andWhere('c.vendedor = :vendedor')
                ->setParameter('vendedor', $filters['vendedor']);
        }

        if (!empty($filters['buscar'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.razonSocial', ':buscar'),
                    $qb->expr()->like('c.codigo', ':buscar'),
                    $qb->expr()->like('c.cuit', ':buscar')
                )
            )
            ->setParameter('buscar', '%' . $filters['buscar'] . '%');
        }

        $qb->orderBy('c.razonSocial', 'ASC');

        return $qb->getQuery()->getResult();
    }
} 